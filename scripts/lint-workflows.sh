#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

ACTIONLINT_TAG=v1.7.12 # tool-pin: rhysd/actionlint@914e7df21a07ef503a81201c76d2b11c789d3fca
SHELLCHECK_TAG=v0.11.0 # tool-pin: koalaman/shellcheck@aac0823e6b58f8a499e856e93738082691cbf212
VALIDATOR_TAG=v0.9.0 # tool-pin: mpalmer/action-validator@c994f427b7c42cd5ecb1bc9315cb91c3d7d72e3d

if [[ "$(uname -s)" != Linux ]]; then
    echo "Workflow validation requires Linux; use the PHP development container." >&2
    exit 1
fi

case "$(uname -m)" in
    x86_64)
        arch=amd64
        shellcheck_arch=x86_64
        actionlint_sha=8aca8db96f1b94770f1b0d72b6dddcb1ebb8123cb3712530b08cc387b349a3d8
        validator_sha=9f42f94fca5b8d04c13bccfbb331104b37a9250650d89ae58dc888d46206f9b9
        shellcheck_sha=b7af85e41cc99489dcc21d66c6d5f3685138f06d34651e6d34b42ec6d54fe6f6
        ;;
    aarch64|arm64)
        arch=arm64
        shellcheck_arch=aarch64
        actionlint_sha=325e971b6ba9bfa504672e29be93c24981eeb1c07576d730e9f7c8805afff0c6
        validator_sha=0fe137986e838c68f760d9a60569cf6749192fa6315915f19ba0c7d11d8e3d9a
        shellcheck_sha=68a8133197a50beb8803f8d42f9908d1af1c5540d4bb05fdfca8c1fa47decefc
        ;;
    *)
        echo "Unsupported architecture for workflow validation: $(uname -m)" >&2
        exit 1
        ;;
esac

tools=$(mktemp -d)
trap 'rm -rf "$tools"' EXIT

download() {
    local url=$1 checksum=$2 file=$3
    curl --fail --silent --show-error --location --retry 3 "$url" --output "$file"
    printf '%s  %s\n' "$checksum" "$file" | sha256sum --check
}

download "https://github.com/rhysd/actionlint/releases/download/${ACTIONLINT_TAG}/actionlint_${ACTIONLINT_TAG#v}_linux_${arch}.tar.gz" \
    "$actionlint_sha" "$tools/actionlint.tar.gz"
tar -xzf "$tools/actionlint.tar.gz" -C "$tools" actionlint
download "https://github.com/koalaman/shellcheck/releases/download/${SHELLCHECK_TAG}/shellcheck-${SHELLCHECK_TAG}.linux.${shellcheck_arch}.tar.gz" \
    "$shellcheck_sha" "$tools/shellcheck.tar.gz"
tar -xzf "$tools/shellcheck.tar.gz" -C "$tools" --strip-components=1 "shellcheck-${SHELLCHECK_TAG}/shellcheck"
download "https://github.com/mpalmer/action-validator/releases/download/${VALIDATOR_TAG}/action-validator_linux_${arch}" \
    "$validator_sha" "$tools/action-validator"
chmod +x "$tools/action-validator"

mapfile -d '' workflows < <(find .github/workflows -maxdepth 1 -type f \( -name '*.yml' -o -name '*.yaml' \) -print0 | sort -z)
mapfile -d '' actions < <(find .github/actions -type f \( -name action.yml -o -name action.yaml \) -print0 | sort -z)
if (( ${#workflows[@]} == 0 || ${#actions[@]} == 0 )); then
    echo "Expected workflow files and local action metadata were not found." >&2
    exit 1
fi

"$tools/actionlint" -shellcheck "$tools/shellcheck" "${workflows[@]}"
for action in "${actions[@]}"; do
    echo "Validating local action metadata: $action"
    "$tools/action-validator" "$action"
done
"$tools/shellcheck" scripts/lint-workflows.sh
