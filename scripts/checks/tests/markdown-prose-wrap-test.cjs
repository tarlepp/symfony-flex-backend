"use strict";

const assert = require("node:assert/strict");
const { spawnSync } = require("node:child_process");

const url = "https://example.test/" + "x".repeat(90) + "(path)";
const cases = [
    ["A short line\nwith more prose.\n", true],
    [`${"a".repeat(75)}\nword fits exactly.\n`, true],
    [`${"a".repeat(76)}\nword moves to the next line.\n`, false],
    [`A short line\n[a long label](${url}) and more.\n`, true],
    [`${"a".repeat(67)}\n[a long label](${url}) and more.\n`, true],
    [`A short line\n[a long label][ref] and more.\n\n[ref]: ${url}\n`, true],
    [`${"a".repeat(70)}\n[a long label](${url}) ends here.\n`, false],
    ["A short line  \nwith a hard break.\n", false],
    ["A short line\\\nwith a hard break.\n", false],
    ["A short line\nbreak\\\nwith a hard break.\n", false],
    ["A short line.\n\nA different paragraph.\n", false],
    ["* First item.\n* Second item.\n", false],
    ["* First line\n  of the same item.\n", true],
    ["```\nA short line\nwith more code.\n```\n", false],
    ["---\ntext: A short line\n  with more metadata.\n---\n", false]
];

for (const [markdown, expected] of cases) {
    const result = spawnSync("npx", ["--yes", "markdownlint-cli2", "-"], {
        input: markdown,
        encoding: "utf8"
    });
    const output = result.stdout + result.stderr;
    assert.ifError(result.error);
    assert.match(output, /Linting: 1 file\b/);
    assert.equal(output.includes("MD999/prose-wrap"), expected, output + markdown);
}
