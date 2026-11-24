#!/usr/bin/env bash

#
# Validate workflow YAML, embedded shell, local action metadata, and
# tooling regressions using SHA-pinned validators.
#
# Usage:
#   From the repository root on the Docker host or in the PHP development container:
#     make lint-workflows
#   Direct invocation from the repository root inside the container:
#     bash scripts/checks/lint-workflows.sh
#
# Details:
#   Downloads can use the local validator cache; the zizmor audit runs offline
#   and does not require a GitHub token.
#
# Callers:
#   Makefile:
#     - `lint-workflows` target.
#   GitHub Actions:
#     - `.github/workflows/main.yml` workflow and local action validation job.
#

set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/../.."
source scripts/output/command-output.sh

OUTPUT_RUN_GROUPED=1
OUTPUT_STATUS_INDENT='  '

OUTPUT_STATUS_INDENT='' output_status RUN '1/5 - Validate CI gate contracts'

output_run 'CI gate regressions (offline)' \
    python3 scripts/output/native-output.py --indent \
    python3 scripts/tests/run-workflow-tests.py scripts/ci/tests/ci-gates-test.py
OUTPUT_STATUS_INDENT='' output_status OK '1/5 - Validate CI gate contracts'

ACTIONLINT_TAG=v1.7.12 # tool-pin: rhysd/actionlint@914e7df21a07ef503a81201c76d2b11c789d3fca
SHELLCHECK_TAG=v0.11.0 # tool-pin: koalaman/shellcheck@aac0823e6b58f8a499e856e93738082691cbf212
ZIZMOR_TAG=v1.32.0 # tool-pin: zizmorcore/zizmor@21bd300700d43d9ba04b0c1fce72a89391ac80b4
VALIDATOR_TAG=v0.9.0 # tool-pin: mpalmer/action-validator@c994f427b7c42cd5ecb1bc9315cb91c3d7d72e3d

if [[ "$(uname -s)" != Linux ]]; then
    echo "Workflow validation requires Linux; use the PHP development container." >&2
    exit 1
fi

case "$(uname -m)" in
    x86_64)
        arch=amd64
        shellcheck_arch=x86_64
        zizmor_target=x86_64-unknown-linux-gnu

        zizmor_sha=bb110cc765eca9cd7efb2e284a956be5974c60027b0970f16397d86208a9957b
        actionlint_sha=8aca8db96f1b94770f1b0d72b6dddcb1ebb8123cb3712530b08cc387b349a3d8
        validator_sha=9f42f94fca5b8d04c13bccfbb331104b37a9250650d89ae58dc888d46206f9b9
        shellcheck_sha=b7af85e41cc99489dcc21d66c6d5f3685138f06d34651e6d34b42ec6d54fe6f6
        ;;
    aarch64|arm64)
        arch=arm64
        shellcheck_arch=aarch64
        zizmor_target=aarch64-unknown-linux-gnu

        zizmor_sha=cb152b207f815116d7fa1c1ee4614f61578803351395052908095e79adfd9fd0
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
    python3 scripts/output/native-output.py --indent \
        python3 scripts/output/native-output.py --indent \
        bash scripts/checks/download-workflow-validator.sh "$1" "$2" "$3" \
        "build/cache/workflow-validators/linux-${arch}/$2"
}

printf '\n' >&2
OUTPUT_STATUS_INDENT='' output_status RUN '2/5 - Prepare pinned workflow validators'

output_run "Prepare and verify actionlint $ACTIONLINT_TAG" download \
    "https://github.com/rhysd/actionlint/releases/download/${ACTIONLINT_TAG}/actionlint_${ACTIONLINT_TAG#v}_linux_${arch}.tar.gz" \
    "$actionlint_sha" "$tools/actionlint.tar.gz"
tar -xzf "$tools/actionlint.tar.gz" -C "$tools" actionlint

output_run "Prepare and verify ShellCheck $SHELLCHECK_TAG" download \
    "https://github.com/koalaman/shellcheck/releases/download/${SHELLCHECK_TAG}/shellcheck-${SHELLCHECK_TAG}.linux.${shellcheck_arch}.tar.gz" \
    "$shellcheck_sha" "$tools/shellcheck.tar.gz"
tar -xzf "$tools/shellcheck.tar.gz" -C "$tools" --strip-components=1 "shellcheck-${SHELLCHECK_TAG}/shellcheck"

output_run "Prepare and verify action-validator $VALIDATOR_TAG" download \
    "https://github.com/mpalmer/action-validator/releases/download/${VALIDATOR_TAG}/action-validator_linux_${arch}" \
    "$validator_sha" "$tools/action-validator"
chmod +x "$tools/action-validator"

output_run "Prepare and verify zizmor $ZIZMOR_TAG" download \
    "https://github.com/zizmorcore/zizmor/releases/download/${ZIZMOR_TAG}/zizmor-${zizmor_target}.tar.gz" \
    "$zizmor_sha" "$tools/zizmor.tar.gz"
tar -xzf "$tools/zizmor.tar.gz" -C "$tools" zizmor
OUTPUT_STATUS_INDENT='' output_status OK '2/5 - Prepare pinned workflow validators'

mapfile -d '' workflows < <(
    find .github/workflows -maxdepth 1 -type f \
        \( -name '*.yml' -o -name '*.yaml' \) -print0 | sort -z
)

mapfile -d '' actions < <(
    find .github/actions -type f \
        \( -name action.yml -o -name action.yaml \) -print0 | sort -z
)

if (( ${#workflows[@]} == 0 || ${#actions[@]} == 0 )); then
    echo "Expected workflow files and local action metadata were not found." >&2
    exit 1
fi

printf '\n' >&2
OUTPUT_STATUS_INDENT='' output_status RUN '3/5 - Validate workflows'

output_run "Workflow syntax and embedded shell (${#workflows[@]} files)" \
    python3 scripts/output/native-output.py --indent \
    python3 scripts/output/native-output.py --indent \
    "$tools/actionlint" -shellcheck "$tools/shellcheck" "${workflows[@]}"

# Offline mode skips audits that need the GitHub API, so no token is needed.
output_run 'Workflow and action security audit (zizmor)' \
    python3 scripts/output/native-output.py --indent \
    python3 scripts/output/native-output.py --indent \
    env RUST_LOG="${RUST_LOG:-warn}" "$tools/zizmor" --no-progress \
    --offline --min-severity low "${workflows[@]}" "${actions[@]}"
OUTPUT_STATUS_INDENT='' output_status OK '3/5 - Validate workflows'

printf '\n' >&2
OUTPUT_STATUS_INDENT='' output_status RUN '4/5 - Validate local actions'

for action in "${actions[@]}"; do
    output_run "Local action metadata: $action" \
        python3 scripts/output/native-output.py --indent \
        python3 scripts/output/native-output.py --indent "$tools/action-validator" "$action"
done

output_run 'Local composite action shell (ShellCheck)' \
    python3 scripts/output/native-output.py --indent \
    python3 scripts/output/native-output.py --indent \
    python3 scripts/checks/check-composite-action-shell.py --shellcheck "$tools/shellcheck" "${actions[@]}"

output_run 'Composite action shell regressions (offline)' \
    python3 scripts/output/native-output.py --indent \
    python3 scripts/tests/run-workflow-tests.py scripts/checks/tests/composite-action-shell-test.py "$tools/shellcheck"
OUTPUT_STATUS_INDENT='' output_status OK '4/5 - Validate local actions'

printf '\n' >&2
OUTPUT_STATUS_INDENT='' output_status RUN '5/5 - Validate tooling scripts'

output_run 'CI tooling scripts (ShellCheck)' \
    python3 scripts/output/native-output.py --indent \
    python3 scripts/output/native-output.py --indent "$tools/shellcheck" \
    scripts/checks/lint-workflows.sh \
    scripts/checks/download-workflow-validator.sh \
    scripts/output/command-output.sh \
    scripts/ci/check-composer-cache.sh \
    scripts/ci/ci-docker-hub-auth.sh \
    scripts/ci/ci-action-update-status.sh \
    scripts/ci/ci-run-analyzer.sh \
    scripts/ci/ci-detect-changes.sh \
    scripts/ci/ci-prerequisite-gate.sh \
    scripts/ci/ci-result-gate.sh \
    scripts/ci/ci-gate-common.sh \
    scripts/ci/ci-workflow-overview.sh \
    scripts/ci/ci-statistics-summary.sh \
    scripts/ci/ci-validate-compose.sh \
    scripts/ci/ci-failure-diagnostics.sh \
    scripts/ci/ci-validate-bootstrap.sh \
    scripts/checks/check-action-updates.sh \
    scripts/docker/docker-images.sh \
    scripts/docker/sync-doctrine-mariadb.sh \
    scripts/docker/sync-ci-mariadb.sh \
    scripts/docker/prepare-php-minor-pr.sh \
    scripts/docker/prepare-image-update-pr.sh \
    scripts/docker/select-maintenance-pr-auth.sh \
    scripts/docker/php-minor-pr-outcome.sh \
    scripts/docker/image-update-overview.sh \
    scripts/sync-composer-php.sh \
    scripts/make/make.sh \
    scripts/make/pre-pr-checks.sh \
    scripts/make/tests/pre-pr-checks-test.sh \
    scripts/checks/check-security.sh \
    scripts/reports/prepare-sonar-reports.sh

output_run 'Composer cache mount regressions (offline)' \
    python3 scripts/output/native-output.py --indent \
    python3 scripts/tests/run-workflow-tests.py scripts/ci/tests/composer-cache-test.py

output_run 'Analyzer job and gate regressions (offline)' \
    python3 scripts/output/native-output.py --indent \
    python3 scripts/tests/run-workflow-tests.py scripts/ci/tests/analyzer-workflow-test.py

output_run 'Docker maintenance workflow regressions (offline)' \
    python3 scripts/output/native-output.py --indent \
    python3 scripts/tests/run-workflow-tests.py scripts/docker/tests/docker-image-workflow-test.py
OUTPUT_STATUS_INDENT='' output_status OK '5/5 - Validate tooling scripts'
