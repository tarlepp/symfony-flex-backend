"use strict";

const limit = 80;
const width = (text) => [...text].length;
const lastRun = /\S+$/;

function fitsSourceWidth(line) {
    // MD013 remains active and permits the final uninterrupted run to overhang.
    return line.replace(lastRun, "#").length <= limit;
}

function* tokensOfType(tokens, types) {
    for (const token of tokens) {
        if (types.has(token.type)) {
            yield token;
        } else {
            yield* tokensOfType(token.children, types);
        }
    }
}

function proseLines(paragraph, lines) {
    const atomic = new Set(["link", "image", "autolink", "codeText", "literalAutolink"]);
    const spans = new Map();
    for (const token of tokensOfType(paragraph.children, atomic)) {
        const { type, startLine, endLine, startColumn, endColumn } = token;
        if (startLine !== endLine) {
            for (let line = startLine; line <= endLine; line++) {
                spans.set(line, null);
            }
        } else if (spans.get(startLine) !== null) {
            const label = token.children.find((child) => child.type === "label");
            const text = label?.children.find((child) => child.type === "labelText");
            const count = (type === "link" || type === "image") ?
                width(text?.text ?? "") : width(token.text);
            const list = spans.get(startLine) ?? [];
            list.push({ start: startColumn - 1, end: endColumn - 1, count });
            spans.set(startLine, list);
        }
    }

    return Array.from({ length: paragraph.endLine - paragraph.startLine + 1 }, (_, index) => {
        const number = paragraph.startLine + index;
        const line = lines[number - 1];
        const ranges = spans.get(number);
        if (ranges === null || line.includes("\t")) {
            return null;
        }
        const start = index === 0 ? paragraph.startColumn - 1 :
            (line.match(/^[ >]*/)?.[0].length ?? 0);
        const words = [];
        let word = null;
        let cursor = start;
        let spanIndex = 0;
        while (cursor < line.length) {
            const span = ranges?.[spanIndex];
            if (span?.start === cursor) {
                word ??= { start: cursor, end: cursor, count: 0 };
                word.count += span.count;
                cursor = span.end;
                spanIndex++;
            } else {
                const char = String.fromCodePoint(line.codePointAt(cursor));
                if (char === " ") {
                    if (word) {
                        words.push(word);
                        word = null;
                    }
                    cursor++;
                    continue;
                }
                word ??= { start: cursor, end: cursor, count: 0 };
                word.count++;
                cursor += char.length;
            }
            word.end = cursor;
        }
        if (word) {
            words.push(word);
        }
        // Keep source indentation, list markers and spacing in the width.
        const count = width(line.trimEnd()) + (ranges ?? []).reduce(
            (delta, { start, end, count: size }) =>
                delta + size - width(line.slice(start, end)),
            0
        );
        return { number, line, words, count };
    });
}

module.exports = {
    names: ["MD999", "prose-wrap"],
    description: "Fill soft-wrapped prose lines to 80 characters at word boundaries",
    tags: ["line_length"],
    parser: "micromark",
    function: (params, onError) => {
        for (const paragraph of tokensOfType(params.parsers.micromark.tokens, new Set(["paragraph"]))) {
            const lines = proseLines(paragraph, params.lines);
            for (const [index, current] of lines.entries()) {
                if (!current?.words.length) {
                    continue;
                }
                const { number, line, count } = current;
                const next = lines[index + 1];
                const following = next?.words[0];
                if (!following || /(?: {2,}|\\)$/.test(line) ||
                    (next.words.length === 1 && /(?: {2,}|\\)$/.test(next.line))) {
                    continue;
                }
                const combined = count + 1 + following.count;
                const candidate = `${line.trimEnd()} ${next.line.slice(following.start, following.end)}`;
                const remainder = next.line.slice(following.end).trimStart();
                if (combined <= limit && fitsSourceWidth(candidate) &&
                    !/^(?:[#>*+-](?: |$)|\d+[.)] |`{3,}|~{3,}|<)/.test(remainder)) {
                    onError({
                        lineNumber: number,
                        detail: `The next word fits on this line (${combined}/${limit}).`
                    });
                }
            }
        }
    }
};
