#!/bin/bash

set -Eeuo pipefail

SCRIPT="$(cd "$(dirname "$0")/.." && pwd)/docker-images.sh"
source "$(dirname "$SCRIPT")/command-output.sh"
WORK="$(mktemp -d)"
passed=0
current_case='fixture setup'
case_active=false
failed_command=''
failed_line=''

finish_case() {
    if [ "$case_active" = true ]; then
        output_status OK "$current_case"
        passed=$((passed + 1))
        case_active=false
    fi
}

start_case() {
    finish_case
    current_case=$1
    case_active=true
    failed_command=''
    failed_line=''
}

start_group() {
    finish_case
    current_case="$1 setup"
    printf '\n' >&2
    output_status RUN "$1"
}

cleanup() {
    rm -f "$WORK/bin/docker" "$WORK/manifest.json" "$WORK/output" "$WORK/summary" \
        "$WORK/advisory-summary" \
        "$WORK/bin/curl" "$WORK/tags.json" "$WORK/requests" \
        "$WORK/bin/sleep" "$WORK/docker-attempts" "$WORK/retry-delays" \
        "$WORK/Dockerfile" "$WORK/docker/nginx/Dockerfile" "$WORK/docker/mariadb/Dockerfile"
    rmdir "$WORK/docker/nginx" "$WORK/docker/mariadb" "$WORK/docker" "$WORK/bin" "$WORK"
}

finish() {
    local status=$?
    trap - EXIT
    if [ "$status" -eq 0 ]; then
        finish_case
        printf '\n' >&2
        output_status OK "Docker image tests: $passed scenarios passed"
    elif [ "$status" -eq 130 ] || [ "$status" -eq 143 ]; then
        output_status WARN "Docker image tests: interrupted during $current_case (exit $status)"
    else
        output_status FAIL "$current_case (exit $status)"
        if [ -n "$failed_command" ]; then
            printf 'Assertion at line %s failed: %s\n' "$failed_line" "$failed_command" >&2
            printf 'Expected the assertion to succeed; actual exit: %s\n' "$status" >&2
        fi
        if [ -f "$WORK/output" ]; then
            printf '%s\n' 'Captured helper output:' >&2
            cat "$WORK/output" >&2
        fi
        output_status FAIL "Docker image tests: $passed scenarios passed before failure"
    fi
    cleanup
    exit "$status"
}

trap 'failed_command=$BASH_COMMAND; failed_line=$LINENO' ERR
trap finish EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

output_status RUN 'Docker image tests'
printf '%s\n' \
    'Offline regression tests using temporary Dockerfiles and mocked Docker/registry responses.' \
    'Actual repository Dockerfile pins are NOT checked. To validate them, run: make docker-image-verify-pins' >&2

mkdir -p "$WORK/bin" "$WORK/docker/nginx" "$WORK/docker/mariadb"
cat > "$WORK/bin/docker" << 'EOF'
#!/bin/bash
set -euo pipefail
if [[ "$*" == "buildx imagetools inspect "* ]] && [ -n "${TEST_RETRY_TARGET:-}" ]; then
    target=digest
    [[ "$*" == *"inspect --raw "* ]] && target=manifest
    if [ "$target" = "$TEST_RETRY_TARGET" ]; then
        attempt=0
        [ ! -f "$TEST_ATTEMPTS" ] || read -r attempt < "$TEST_ATTEMPTS"
        attempt=$((attempt + 1))
        printf '%s\n' "$attempt" > "$TEST_ATTEMPTS"
        if [ "$attempt" -le "${TEST_RETRY_FAILURES:-1}" ]; then
            printf '%s\n' "${TEST_RETRY_MESSAGE:-unexpected status from HEAD request: 429 Too Many Requests}" >&2
            exit 1
        fi
    fi
fi
case "$*" in
    "buildx version") printf 'test buildx\n' ;;
    "buildx imagetools inspect --raw "*)
        if [ "${TEST_REQUIRE_PIN:-}" = 1 ] && ! [[ "${!#}" =~ @sha256:[a-f0-9]{64}$ ]]; then
            printf 'Update manifest must be inspected by digest\n' >&2
            exit 1
        fi
        if [ "${TEST_DOCKER_FAILURE:-}" = manifest ]; then
            printf 'Manifest unavailable\n' >&2
            exit 1
        fi
        if [ -n "${TEST_CURRENT_MANIFEST:-}" ] && [[ "${!#}" == *@sha256:bbbb* ]]; then
            printf '%s\n' "$TEST_CURRENT_MANIFEST"
        else
            cat "$TEST_MANIFEST"
        fi
        ;;
    "buildx imagetools inspect "*" --format {{.Manifest.Digest}}")
        if [ "${TEST_DOCKER_FAILURE:-}" = digest ]; then
            printf 'Digest lookup failed\n' >&2
            exit 1
        fi
        ref="${@: -3:1}"
        if [[ -n "${TEST_VERIFY_TAG_DIGEST+x}" ]] && [[ "$ref" != *@* ]]; then
            if [ "${TEST_VERIFY_TAG_FAILURE:-}" = 1 ]; then
                printf 'Tag digest lookup failed\n' >&2
                exit 1
            fi
            printf '%s\n' "$TEST_VERIFY_TAG_DIGEST"
        else
            printf '%s\n' "${TEST_DIGEST:-sha256:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa}"
        fi
        ;;
    *) printf 'Unexpected Docker command: %s\n' "$*" >&2; exit 1 ;;
esac
EOF
chmod +x "$WORK/bin/docker"
cat > "$WORK/bin/sleep" << 'EOF'
#!/bin/bash
set -euo pipefail
if [ -n "${TEST_RETRY_TARGET:-}" ]; then
    if ! grep -Fq "Press Ctrl+C to stop, then run 'docker login docker.io'." "$TEST_OUTPUT"; then
        printf 'FAIL: login and cancellation guidance was not visible before waiting\n' >&2
        exit 1
    fi
    attempt="$(cat "$TEST_ATTEMPTS")"
    remaining=$((attempt * 10 - $(wc -l < "$TEST_RETRY_DELAYS") * 5))
    if [ "$attempt" -eq 2 ]; then
        remaining=$((remaining + 10))
    fi
    grep -Fq "attempt $((attempt + 1))/3 in ${remaining}s." "$TEST_OUTPUT"
fi
printf '%s\n' "$*" >> "$TEST_RETRY_DELAYS"
EOF
chmod +x "$WORK/bin/sleep"

export PATH="$WORK/bin:$PATH"
export TEST_MANIFEST="$WORK/manifest.json"
export TEST_ATTEMPTS="$WORK/docker-attempts"
export TEST_RETRY_DELAYS="$WORK/retry-delays"
export TEST_OUTPUT="$WORK/output"
export NO_COLOR=1
export IMAGE=test:fixture
export GITHUB_ACTIONS=true
export GITHUB_STEP_SUMMARY="$WORK/summary"

for file in Dockerfile docker/nginx/Dockerfile docker/mariadb/Dockerfile; do
    printf 'FROM test:fixture@sha256:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa AS base\nFROM base AS final\n' > "$WORK/$file"
done

cd "$WORK"

check() {
    local name="$1" expected="$2" manifest="$3" message="$4"
    local command status

    start_case "$name (digest and verify)"
    printf '%s\n' "$manifest" > "$TEST_MANIFEST"

    for command in digest verify; do
        : > "$GITHUB_STEP_SUMMARY"
        status=0
        bash "$SCRIPT" "$command" > "$WORK/output" 2>&1 || status=$?

        if [ "$status" -ne "$expected" ]; then
            printf 'FAIL: %s (%s): expected exit %s, got %s\n' "$name" "$command" "$expected" "$status"
            cat "$WORK/output"
            exit 1
        fi

        if [ "$expected" -eq 0 ]; then
            grep -q 'linux/amd64 linux/arm64/v8' "$WORK/output"
        else
            grep -q "$message" "$WORK/output"
        fi

        if [ "$command" = digest ]; then
            if [ "$expected" -eq 0 ]; then
                grep -q 'Pin reference:' "$WORK/output"
            elif grep -q 'Pin reference:' "$WORK/output"; then
                printf 'FAIL: %s: rejected image printed a pin reference\n' "$name"
                exit 1
            fi
        elif [ "$expected" -ne 0 ]; then
            grep -q '::error file=Dockerfile,line=1' "$WORK/output"
            grep -q ':x:' "$GITHUB_STEP_SUMMARY"
        fi
    done

}

start_group 'Manifest validation'
check 'OCI multi-platform index' 0 \
    '{"mediaType":"application/vnd.oci.image.index.v1+json","manifests":[{"platform":{"os":"linux","architecture":"amd64"}},{"platform":{"os":"linux","architecture":"arm64","variant":"v8"}},{"platform":{"os":"unknown","architecture":"unknown"}}]}' ''
check 'Docker multi-platform list' 0 \
    '{"mediaType":"application/vnd.docker.distribution.manifest.list.v2+json","manifests":[{"platform":{"os":"linux","architecture":"arm64","variant":"v8"}},{"platform":{"os":"linux","architecture":"amd64"}}]}' ''
check 'empty index' 1 \
    '{"mediaType":"application/vnd.oci.image.index.v1+json","manifests":[]}' \
    'fewer than two distinct runnable platforms (none)'
check 'one platform plus attestation' 1 \
    '{"mediaType":"application/vnd.oci.image.index.v1+json","manifests":[{"platform":{"os":"linux","architecture":"amd64"}},{"platform":{"os":"unknown","architecture":"unknown"},"annotations":{"vnd.docker.reference.type":"attestation-manifest"}}]}' \
    'fewer than two distinct runnable platforms (linux/amd64)'
check 'duplicate platforms' 1 \
    '{"mediaType":"application/vnd.oci.image.index.v1+json","manifests":[{"platform":{"os":"linux","architecture":"amd64"}},{"platform":{"os":"linux","architecture":"amd64"}}]}' \
    'fewer than two distinct runnable platforms (linux/amd64)'
check 'missing and unknown platform fields' 1 \
    '{"mediaType":"application/vnd.oci.image.index.v1+json","manifests":[{},{"platform":{}},{"platform":{"os":"linux"}},{"platform":{"architecture":"amd64"}},{"platform":{"os":"linux","architecture":"unknown"}},{"platform":{"os":"","architecture":"amd64"}}]}' \
    'fewer than two distinct runnable platforms (none)'
check 'attestation with platform metadata' 1 \
    '{"mediaType":"application/vnd.oci.image.index.v1+json","manifests":[{"platform":{"os":"linux","architecture":"amd64"}},{"platform":{"os":"linux","architecture":"arm64"},"annotations":{"vnd.docker.reference.type":"attestation-manifest"}}]}' \
    'fewer than two distinct runnable platforms (linux/amd64)'
check 'single-platform manifest' 1 \
    '{"mediaType":"application/vnd.oci.image.manifest.v1+json","config":{},"layers":[]}' \
    '[Ss]ingle platform'

verify_pin_parity() {
    local name="$1" tag_digest="$2" expected="$3" message="$4" failure="${5:-}" status=0

    start_case "$name"
    : > "$GITHUB_STEP_SUMMARY"
    TEST_VERIFY_TAG_DIGEST="$tag_digest" TEST_VERIFY_TAG_FAILURE="$failure" \
        bash "$SCRIPT" verify > "$WORK/output" 2>&1 || status=$?
    if [ "$status" -ne "$expected" ] || ! grep -Fq -- "$message" "$WORK/output"; then
        printf 'FAIL: %s: expected exit %s and message "%s", got exit %s\n' \
            "$name" "$expected" "$message" "$status"
        cat "$WORK/output"
        exit 1
    fi
}

start_group 'Pinned tag/digest parity'
printf '{"mediaType":"application/vnd.oci.image.index.v1+json","manifests":[{"platform":{"os":"linux","architecture":"amd64"}},{"platform":{"os":"linux","architecture":"arm64","variant":"v8"}}]}\n' \
    > "$TEST_MANIFEST"
verify_pin_parity 'matching tag digest' \
    'sha256:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa' 0 \
    'Tag digest matches'
verify_pin_parity 'mismatched tag digest fails verification' \
    'sha256:bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb' 1 \
    'Tag digest mismatch: tag resolves to sha256:bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'
grep -Fq '::error file=Dockerfile,line=1,title=Tag and digest do not match::' "$WORK/output"
verify_pin_parity 'tag digest lookup failure fails verification' \
    'sha256:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa' 1 \
    'Could not resolve current tag digest' 1
verify_pin_parity 'invalid tag digest fails verification' 'invalid-digest' 1 \
    'Invalid digest for current tag: invalid-digest'
printf 'FROM test@sha256:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa AS base\nFROM base AS final\n' \
    > "$WORK/Dockerfile"
verify_pin_parity 'digest-only reference warns that parity cannot be checked' \
    'sha256:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa' 0 \
    'Cannot verify tag/digest parity: reference has no explicit tag'
grep -Fq '::warning file=Dockerfile,line=1' "$WORK/output"
grep -Fq 'OK (with warnings)' "$WORK/output"
printf 'FROM test:fixture@sha256:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa AS base\nFROM base AS final\n' \
    > "$WORK/Dockerfile"

start_group 'External COPY pin verification'
for file in docker/nginx/Dockerfile docker/mariadb/Dockerfile; do
    printf 'FROM scratch\n' > "$WORK/$file"
done
cat > "$WORK/Dockerfile" << 'EOF'
FROM \
    --platform=linux/amd64 scratch AS base
FROM BASE AS final
COPY --from=BASE /bin/tool /bin/tool
COPY --from=0 /bin/tool /bin/tool
# External tool image follows.
COPY \
    # Keep the instruction start line in diagnostics.

    --from=test:fixture@sha256:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa \
    /bin/tool /bin/tool
RUN printf "not an instruction" \
    COPY --from=ignored/tool:fixture /bin/tool /bin/tool
EOF
verify_pin_parity 'multiline external COPY pin and local stage exclusions' \
    'sha256:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa' 0 \
    'Tag digest matches'
grep -Fq 'line 7: test:fixture@sha256:' "$WORK/output"
grep -Fq '| `Dockerfile` | 7 | `test:fixture@sha256:' "$GITHUB_STEP_SUMMARY"
[ "$(grep -c 'Tag digest matches' "$WORK/output")" -eq 1 ]
if grep -q '\[WARN\]\|ignored/tool\|line [2345]:' "$WORK/output"; then
    printf 'FAIL: verification included a local stage, scratch or a continued RUN\n'
    exit 1
fi
verify_pin_parity 'external COPY tag mismatch fails with instruction location' \
    'sha256:bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb' 1 \
    'Tag digest mismatch'
grep -Fq '::error file=Dockerfile,line=7,title=Tag and digest do not match::' "$WORK/output"
grep -Fq ':x: Multi-platform:' "$GITHUB_STEP_SUMMARY"
verify_pin_parity 'external COPY tag lookup failure fails verification' \
    'sha256:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa' 1 \
    'Could not resolve current tag digest' 1
verify_pin_parity 'external COPY invalid tag digest fails verification' \
    'invalid-digest' 1 'Invalid digest for current tag'
TEST_DOCKER_FAILURE=manifest verify_pin_parity \
    'external COPY manifest lookup failure fails verification' \
    'sha256:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa' 1 \
    'Could not inspect image'
printf '{"mediaType":"application/vnd.oci.image.manifest.v1+json","config":{},"layers":[]}\n' \
    > "$TEST_MANIFEST"
verify_pin_parity 'external COPY single-platform digest fails verification' \
    'sha256:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa' 1 \
    'Single platform digest'
printf '{"mediaType":"application/vnd.oci.image.index.v1+json","manifests":[{"platform":{"os":"linux","architecture":"amd64"}}]}\n' \
    > "$TEST_MANIFEST"
verify_pin_parity 'external COPY single-platform index fails verification' \
    'sha256:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa' 1 \
    'fewer than two distinct runnable platforms'
printf 'FROM scratch\nCOPY --from=test:fixture /bin/tool /bin/tool\n' > "$WORK/Dockerfile"
verify_pin_parity 'unpinned external COPY warns without failing' \
    'sha256:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa' 0 \
    'Not pinned with digest'
grep -Fq '::warning file=Dockerfile,line=2' "$WORK/output"
grep -Fq 'OK (with warnings)' "$WORK/output"
for file in Dockerfile docker/nginx/Dockerfile docker/mariadb/Dockerfile; do
    printf 'FROM test:fixture@sha256:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa AS base\nFROM base AS final\n' > "$WORK/$file"
done

start_group 'Version discovery and output formatting'
printf '{"mediaType":"application/vnd.oci.image.index.v1+json","manifests":[{"platform":{"os":"linux","architecture":"amd64"}},{"platform":{"os":"linux","architecture":"arm64","variant":"v8"}}]}\n' > "$TEST_MANIFEST"
export TEST_REQUIRE_PIN=1

cat > "$WORK/bin/curl" << 'EOF'
#!/bin/bash
set -euo pipefail
printf '%s\n' "${!#}" >> "$TEST_REQUESTS"
case "${TEST_TAG_MODE:-}" in
    failure) printf 'Registry unavailable\n' >&2; exit 22 ;;
    invalid) printf '{"message":"not a tags response"}\n' ;;
    cycle)
        if [ "$(wc -l < "$TEST_REQUESTS")" -gt 3 ]; then
            printf 'Pagination cycle was not detected\n' >&2
            exit 1
        fi
        if [[ "${!#}" == *'page=2'* ]]; then
            printf '{"results":[],"next":"https://hub.docker.com/v2/repositories/library/test/tags/?page=3"}\n'
        else
            printf '{"results":[],"next":"https://hub.docker.com/v2/repositories/library/test/tags/?page=2"}\n'
        fi
        ;;
    pagination)
        if [[ "${!#}" == *'page=2'* ]]; then
            printf '{"next":null,"results":[{"name":"10.0.0-fpm-bookworm"},{"name":"99.0.0-fpm-alpine"},{"name":"11.0.0RC1-fpm-bookworm"},{"name":"12.0.0-rc-fpm-bookworm"}]}\n'
        else
            printf '{"next":"https://hub.docker.com/v2/repositories/library/test/tags/?page=2","results":[{"name":"1.10.0-fpm-bookworm"},{"name":"1.9.9-fpm-bookworm"},{"name":"9.0.0-fpm-bookworm"}]}\n'
        fi
        ;;
    *) cat "$TEST_TAGS" ;;
esac
EOF
chmod +x "$WORK/bin/curl"
export TEST_TAGS="$WORK/tags.json"
export TEST_REQUESTS="$WORK/requests"
export TEST_TAG_MODE=''
printf '{"next":null,"results":[{"name":"1.9.9"},{"name":"1.10.0"},{"name":"2.0.0"},{"name":"2.0"},{"name":"latest"},{"name":"3.0.0-rc1"},{"name":"4.0.0-alpine"},{"name":"v5.0.0"}]}\n' > "$TEST_TAGS"

check_updates() {
    local name="$1" image="$2" expected="$3" message="$4" status=0

    start_case "$name"
    : > "$TEST_REQUESTS"
    IMAGE="$image" bash "$SCRIPT" check-updates "${@:5}" > "$WORK/output" 2>&1 || status=$?
    if [ "$status" -ne "$expected" ] || ! grep -Fq -- "$message" "$WORK/output"; then
        printf 'FAIL: %s: expected exit %s and message "%s", got exit %s\n' "$name" "$expected" "$message" "$status"
        cat "$WORK/output"
        exit 1
    fi
}

check_updates 'stable major upgrade, excluding variants and prereleases' 'test:1.9.9@sha256:aaaa' 1 \
    '[WARN] test:1.9.9 -> test:2.0.0'
grep -Fxq 'IMAGE input (no matching Dockerfile)' "$WORK/output"
grep -Fq 'Multi-platform pin: test:2.0.0@sha256:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa' "$WORK/output"
grep -Fq 'Platforms: linux/amd64 linux/arm64/v8' "$WORK/output"
if grep -q $'\033' "$WORK/output"; then
    printf 'FAIL: NO_COLOR output contains ANSI escapes\n'
    exit 1
fi
check_updates 'advisory updates succeed with warning annotation and summary' 'test:1.9.9' 0 \
    '[WARN] 1 image(s)' --advisory
GITHUB_ACTIONS=true GITHUB_STEP_SUMMARY="$WORK/advisory-summary" \
    check_updates 'hosted advisory warning is visible' 'test:1.9.9' 0 \
    '::warning file=' --advisory
grep -Fq 'Updates are advisory' "$WORK/advisory-summary"
check_updates 'invalid advisory option fails' 'test:1.9.9' 64 \
    'Unknown check-updates option' --unknown
check_updates 'duplicate advisory option fails' 'test:1.9.9' 64 \
    'accepts only the optional --advisory flag' --advisory --advisory
COLOR=always NO_COLOR='' check_updates 'updates use yellow' 'test:1.9.9' 1 $'\033[33m[WARN]\033[0m'
grep -Fq $'\033[33m[WARN]\033[0m 1 image(s)' "$WORK/output"
COLOR=always NO_COLOR='' check_updates 'up-to-date images use green' 'test:2.0.0' 0 $'\033[32m[OK]\033[0m'
check_updates 'up-to-date image' 'test:2.0.0' 0 '[OK] test:2.0.0: no newer stable version'
if grep -q 'Multi-platform pin:' "$WORK/output"; then
    printf 'FAIL: up-to-date image printed an update pin\n'
    exit 1
fi
check_updates 'advisory current image succeeds' 'test:2.0.0' 0 \
    'no newer stable version' --advisory
check_updates 'local version newer than registry' 'test:9.0.0' 0 'no newer stable version'
check_updates 'Docker Hub prefix and v prefix' 'docker.io/test:v1.0.0' 1 \
    'docker.io/test:v1.0.0 -> docker.io/test:v5.0.0'
grep -Fq '/repositories/library/test/tags/' "$TEST_REQUESTS"
check_updates 'namespaced repository' 'mlocati/test:1.0.0' 1 'mlocati/test:1.0.0 -> mlocati/test:2.0.0'
grep -Fq '/repositories/mlocati/test/tags/' "$TEST_REQUESTS"
check_updates 'unversioned image' 'test' 0 '[SKIP]'
check_updates 'floating version' 'test:1.9' 0 '[SKIP]'
check_updates 'prerelease version' 'test:1.9.9-rc1' 0 '[SKIP]'
grep -Fq '1 image version check(s) skipped' "$WORK/output"
[ ! -s "$TEST_REQUESTS" ]
check_updates 'unsupported registry' 'ghcr.io/owner/test:1.0.0' 1 'supports Docker Hub only'
check_updates 'registry with port' 'localhost:5000/test:1.0.0' 1 'supports Docker Hub only'
check_updates 'no matching variant' 'test:1.0.0-fpm-bookworm' 1 'no matching stable version tags found'

start_group 'Registry responses and pagination'
export TEST_TAG_MODE=pagination
check_updates 'all pages, numeric sorting and exact variant' 'test:1.9.9-fpm-bookworm' 1 \
    'test:1.9.9-fpm-bookworm -> test:10.0.0-fpm-bookworm'
[ "$(wc -l < "$TEST_REQUESTS")" -eq 2 ]
export TEST_TAG_MODE=cycle
check_updates 'multi-page pagination cycle' 'test:1.0.0' 1 'Repeated Docker Hub pagination URL'
[ "$(wc -l < "$TEST_REQUESTS")" -eq 3 ]
export TEST_TAG_MODE=failure
check_updates 'registry failure' 'test:1.0.0' 1 'Could not fetch Docker Hub tags'
check_updates 'advisory registry failure still fails' 'test:1.0.0' 1 \
    'Could not fetch Docker Hub tags' --advisory
export TEST_TAG_MODE=invalid
check_updates 'malformed registry response' 'test:1.0.0' 1 'Invalid Docker Hub tag response'
export TEST_TAG_MODE=''
printf '{"next":"https://example.com/other","results":[]}\n' > "$TEST_TAGS"
check_updates 'unexpected pagination host' 'test:1.0.0' 1 'Unexpected Docker Hub pagination URL'
printf '{"next":"https://hub.docker.com/v2/repositories/library/test/tags/?page_size=100","results":[]}\n' > "$TEST_TAGS"
check_updates 'repeated pagination page' 'test:1.0.0' 1 'Repeated Docker Hub pagination URL'

start_group 'Dockerfile discovery'
printf '{"next":null,"results":[{"name":"2.0.0"}]}\n' > "$TEST_TAGS"
cat > "$WORK/Dockerfile" << 'EOF'
FROM --platform=linux/amd64 test:1.0.0@sha256:aaaa AS base
COPY --from=tools/installer:1.0.0@sha256:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa /bin/tool /bin/tool
COPY --from=tools/installer:1.0.0 /bin/tool /bin/tool
FROM BASE AS development
COPY --from=base /bin/tool /bin/tool
COPY --from=0 /bin/tool /bin/tool
FROM scratch AS production
EOF
printf 'FROM test:1.0.0\n' > "$WORK/docker/nginx/Dockerfile"
printf 'FROM test:1.0.0\n' > "$WORK/docker/mariadb/Dockerfile"
check_updates 'default Dockerfile discovery, COPY tools and deduplication' '' 1 \
    'tools/installer:1.0.0 -> tools/installer:2.0.0'
[ "$(wc -l < "$TEST_REQUESTS")" -eq 2 ]
grep -Fq '[WARN] 2 image(s) require attention' "$WORK/output"
awk '/^\[WARN\] [0-9]+ image\(s\)/ { if (previous != "") exit 1; found = 1 } { previous = $0 } END { if (!found) exit 1 }' "$WORK/output"
grep -Fq 'test:1.0.0 -> test:2.0.0' "$WORK/output"
awk '
    /^Dockerfile$/ { group = "root"; root++; next }
    /^docker\/mariadb\/Dockerfile$/ { group = "mariadb"; mariadb++; next }
    /^docker\/nginx\/Dockerfile$/ { group = "nginx"; nginx++; next }
    /test:1.0.0 -> test:2.0.0/ { updates[group]++ }
    /tools\/installer:1.0.0 -> tools\/installer:2.0.0/ {
        if (group != "root") exit 1
        tools++
    }
    END {
        if (root != 1 || mariadb != 1 || nginx != 1 || tools != 1 ||
            updates["root"] != 1 || updates["mariadb"] != 1 || updates["nginx"] != 1) exit 1
    }
' "$WORK/output"
grep -Fq 'Multi-platform pin: tools/installer:2.0.0@sha256:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa' "$WORK/output"
if grep -q 'SKIP\|FAIL' "$WORK/output"; then
    cat "$WORK/output"
    exit 1
fi

cat > "$WORK/Dockerfile" << 'EOF'
FROM \
    --platform=linux/amd64 \
    test:1.0.0 AS \
    base
COPY \
    # FROM ignored/comment:1.0.0

    --from=tools/installer:1.0.0 \
    /bin/tool /bin/tool
FROM \
    BASE AS final
COPY \
    --from=base /bin/tool /bin/tool
COPY \
    --from=0 /bin/tool /bin/tool
RUN printf "not an instruction" \
    COPY --from=ignored/tool:1.0.0
EOF
check_updates 'multiline FROM and COPY with comments and blank lines' '' 1 \
    'tools/installer:1.0.0 -> tools/installer:2.0.0'
[ "$(wc -l < "$TEST_REQUESTS")" -eq 2 ]
grep -Fq 'test:1.0.0 -> test:2.0.0' "$WORK/output"
if grep -q 'ignored/\|SKIP\|FAIL' "$WORK/output"; then
    cat "$WORK/output"
    exit 1
fi

check_updates 'single image retains matching Dockerfiles' 'test:1.0.0' 1 \
    'docker/mariadb/Dockerfile'
grep -Fxq 'Dockerfile' "$WORK/output"
grep -Fxq 'docker/nginx/Dockerfile' "$WORK/output"
[ "$(wc -l < "$TEST_REQUESTS")" -eq 1 ]
if grep -q 'tools/installer' "$WORK/output"; then
    printf 'FAIL: single image filter included another image\n'
    exit 1
fi
start_group 'Registry lookup failures and retries'
for failure in digest manifest; do
    export TEST_DOCKER_FAILURE="$failure"
    COLOR=always NO_COLOR='' check_updates "update ${failure} lookup failure" 'test:1.0.0' 1 '[FAIL]'
    grep -Fq $'\033[31m[FAIL]\033[0m' "$WORK/output"
    grep -Fq '1 image check(s) failed; 1 image update(s) available' "$WORK/output"
    if grep -q 'Multi-platform pin:' "$WORK/output"; then
        printf 'FAIL: failed update printed a pin reference\n'
        exit 1
    fi
done
export TEST_DOCKER_FAILURE=''
for target in digest manifest; do
    export TEST_RETRY_TARGET="$target"
    export TEST_RETRY_FAILURES=2
    printf '0\n' > "$TEST_ATTEMPTS"
    : > "$TEST_RETRY_DELAYS"
    check_updates "${target} transient rate limit recovers" 'test:1.0.0' 1 \
        'Multi-platform pin: test:2.0.0@sha256:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'
    [ "$(cat "$TEST_ATTEMPTS")" -eq 3 ]
    grep -Fq 'RETRY Registry request failed' "$WORK/output"
    grep -Fq "Run 'docker login docker.io'" "$WORK/output"
    awk '
        /Docker Hub returned 429/ { notified = 1 }
        /RETRY/ { if (!notified) exit 1; retried = 1 }
        END { if (!retried) exit 1 }
    ' "$WORK/output"
    [ "$(cat "$TEST_RETRY_DELAYS")" = $'5\n5\n5\n5\n5\n5' ]
    grep -Fq 'Starting registry request attempt 3/3' "$WORK/output"

    export TEST_RETRY_FAILURES=3
    printf '0\n' > "$TEST_ATTEMPTS"
    : > "$TEST_RETRY_DELAYS"
    check_updates "${target} persistent rate limit fails" 'test:1.0.0' 1 \
        "docker login docker.io"
    [ "$(cat "$TEST_ATTEMPTS")" -eq 3 ]
    [ "$(wc -l < "$TEST_RETRY_DELAYS")" -eq 6 ]
    grep -Fq '429 Too Many Requests' "$WORK/output"
    if grep -q 'Multi-platform pin:' "$WORK/output"; then
        printf 'FAIL: rate-limited lookup printed a pin\n'
        exit 1
    fi

    export TEST_RETRY_MESSAGE='manifest unknown: 404 Not Found'
    printf '0\n' > "$TEST_ATTEMPTS"
    : > "$TEST_RETRY_DELAYS"
    check_updates "${target} permanent error is not retried" 'test:1.0.0' 1 \
        '404 Not Found'
    [ "$(cat "$TEST_ATTEMPTS")" -eq 1 ]
    [ ! -s "$TEST_RETRY_DELAYS" ]
    unset TEST_RETRY_MESSAGE
done
start_group 'Update-pin validation'
unset TEST_RETRY_TARGET TEST_RETRY_FAILURES
export TEST_DIGEST=invalid
check_updates 'invalid update digest' 'test:1.0.0' 1 'Invalid index digest'
unset TEST_DIGEST
rejected_update_names=(
    'single-platform image manifest'
    'index with only one supported platform'
    'empty image index'
    'malformed manifest JSON'
)
rejected_update_manifests=(
    '{"mediaType":"application/vnd.oci.image.manifest.v1+json","config":{},"layers":[]}'
    '{"mediaType":"application/vnd.oci.image.index.v1+json","manifests":[{"platform":{"os":"linux","architecture":"amd64"}},{"platform":{"os":"unknown","architecture":"unknown"}}]}'
    '{"mediaType":"application/vnd.oci.image.index.v1+json","manifests":[]}'
    'invalid-json'
)
for index in "${!rejected_update_manifests[@]}"; do
    manifest=${rejected_update_manifests[$index]}
    printf '%s\n' "$manifest" > "$TEST_MANIFEST"
    check_updates "reject update: ${rejected_update_names[$index]}" 'test:1.0.0' 1 '[FAIL]'
    check_updates "advisory rejects update: ${rejected_update_names[$index]}" \
        'test:1.0.0' 1 '[FAIL]' --advisory
    if grep -q 'Multi-platform pin:' "$WORK/output"; then
        printf 'FAIL: rejected update printed a pin reference\n'
        exit 1
    fi
done
printf '{"mediaType":"application/vnd.docker.distribution.manifest.list.v2+json","manifests":[{"platform":{"os":"linux","architecture":"amd64"}},{"platform":{"os":"linux","architecture":"arm64","variant":"v8"}}]}\n' > "$TEST_MANIFEST"
check_updates 'Docker manifest list update pin' 'test:1.0.0' 1 \
    'Multi-platform pin: test:2.0.0@sha256:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'

start_group 'Aggregate results and empty discovery'
printf 'FROM ghcr.io/owner/test:1.0.0\nCOPY --from=tools/installer:1.0.0 /bin/tool /bin/tool\n' > "$WORK/Dockerfile"
check_updates 'lookup errors do not prevent remaining checks' '' 1 \
    'tools/installer:1.0.0 -> tools/installer:2.0.0'
check_updates 'advisory mixed updates and errors still fail' '' 1 \
    'image check(s) failed' --advisory
grep -Fq 'supports Docker Hub only' "$WORK/output"
printf '{"next":null,"results":[{"name":"1.0.0"}]}\n' > "$TEST_TAGS"
printf 'FROM test:1.0.0\n' > "$WORK/Dockerfile"
check_updates 'all Dockerfiles up to date exit successfully' '' 0 'no newer stable version'
[ "$(wc -l < "$TEST_REQUESTS")" -eq 1 ]
for file in Dockerfile docker/nginx/Dockerfile docker/mariadb/Dockerfile; do
    printf 'FROM scratch\n' > "$WORK/$file"
done
check_updates 'empty image discovery fails explicitly' '' 1 'No external Dockerfile images found'

start_group 'Applying minor/patch updates and digest refreshes'
export IMAGE=''
export TEST_TAG_MODE=''
export TEST_REQUIRE_PIN=1
printf '{"next":null,"results":[{"name":"1.0.0"},{"name":"1.10.0"},{"name":"1.9.9"},{"name":"2.0.0"},{"name":"1.11.0-rc1"},{"name":"1.99.0-alpine"}]}\n' > "$TEST_TAGS"
old_pin="test:1.0.0@sha256:$(printf 'b%.0s' {1..64})"
new_pin="test:1.10.0@sha256:$(printf 'a%.0s' {1..64})"

update_pins() {
    local name="$1" expected="$2" message="$3" status=0
    start_case "$name"
    : > "$TEST_REQUESTS"
    bash "$SCRIPT" update-pins "${@:4}" > "$WORK/output" 2>&1 || status=$?
    if [ "$status" -ne "$expected" ] || ! grep -Fq "$message" "$WORK/output"; then
        printf 'FAIL: %s: expected exit %s and "%s", got %s\n' "$name" "$expected" "$message" "$status"
        cat "$WORK/output"
        exit 1
    fi
}

for file in Dockerfile docker/nginx/Dockerfile docker/mariadb/Dockerfile; do
    printf 'FROM %s AS base\nFROM base AS final\n' "$old_pin" > "$WORK/$file"
done
update_pins 'minor/patch updates preserve major and deduplicate lookups' 0 '3 Dockerfile(s) changed'
[ "$(wc -l < "$TEST_REQUESTS")" -eq 1 ]
for file in Dockerfile docker/nginx/Dockerfile docker/mariadb/Dockerfile; do
    grep -Fxq "FROM $new_pin AS base" "$file"
    grep -Fxq 'FROM base AS final' "$file"
done
update_pins 'idempotent updates make no changes' 0 '0 Dockerfile(s) changed'
for file in docker/nginx/Dockerfile docker/mariadb/Dockerfile; do
    printf 'FROM scratch\n' > "$file"
done

printf '{"next":null,"results":[{"name":"1.0.0"},{"name":"2.0.0"}]}\n' > "$TEST_TAGS"
printf 'FROM %s\n' "$old_pin" > Dockerfile
update_pins 'same-tag digest drift is refreshed without major upgrade' 0 'Dockerfile(s) changed'
grep -Fxq "FROM test:1.0.0@${new_pin##*@}" Dockerfile

printf '{"next":null,"results":[{"name":"v1.0.0-bookworm"},{"name":"v1.2.0-bookworm"},{"name":"v2.0.0-bookworm"},{"name":"v1.9.0-alpine"}]}\n' > "$TEST_TAGS"
printf 'FROM test:v1.0.0-bookworm@%s\n' "${old_pin##*@}" > Dockerfile
update_pins 'version prefix and exact variant are preserved' 0 'Dockerfile(s) changed'
grep -Fxq "FROM test:v1.2.0-bookworm@${new_pin##*@}" Dockerfile

for file in docker/nginx/Dockerfile docker/mariadb/Dockerfile; do
    printf 'FROM scratch\n' > "$file"
done
printf 'FROM test:stable@%s\n' "${old_pin##*@}" > Dockerfile
update_pins 'floating tag receives only a digest refresh' 0 'Dockerfile(s) changed'
[ ! -s "$TEST_REQUESTS" ]
grep -Fxq "FROM test:stable@${new_pin##*@}" Dockerfile

printf '{"next":null,"results":[{"name":"1.0.0"},{"name":"1.10.0"},{"name":"2.0.0"}]}\n' > "$TEST_TAGS"
cat > Dockerfile << EOF
# FROM $old_pin
FROM \\
    --platform=linux/amd64 \\
    $old_pin AS base
COPY \\
    # Keep $old_pin in this comment.

    --from=$old_pin \\
    /bin/tool /bin/tool
FROM BASE AS final
COPY --from=base /bin/tool /bin/tool
COPY --from=0 /bin/tool /bin/tool
RUN echo $old_pin
EOF
update_pins 'continued FROM and COPY edits preserve comments and other instructions' 0 '1 Dockerfile(s) changed'
grep -Fxq "    $new_pin AS base" Dockerfile
grep -Fxq "    --from=$new_pin \\" Dockerfile
grep -Fxq "# FROM $old_pin" Dockerfile
grep -Fxq "    # Keep $old_pin in this comment." Dockerfile
grep -Fxq "RUN echo $old_pin" Dockerfile
grep -Fxq 'COPY --from=base /bin/tool /bin/tool' Dockerfile
grep -Fxq 'COPY --from=0 /bin/tool /bin/tool' Dockerfile

printf 'FROM %s\n' "$old_pin" > Dockerfile
printf 'FROM ghcr.io/owner/test:1.0.0@%s\n' "${old_pin##*@}" > docker/nginx/Dockerfile
before="$(sha256sum Dockerfile docker/nginx/Dockerfile)"
update_pins 'a later registry error prevents all file edits' 1 'supports Docker Hub only'
[ "$before" = "$(sha256sum Dockerfile docker/nginx/Dockerfile)" ]
printf 'FROM scratch\n' > docker/nginx/Dockerfile

export TEST_CURRENT_MANIFEST='{"mediaType":"application/vnd.oci.image.index.v1+json","manifests":[{"platform":{"os":"linux","architecture":"amd64"}},{"platform":{"os":"linux","architecture":"arm64","variant":"v8"}},{"platform":{"os":"linux","architecture":"s390x"}}]}'
before="$(sha256sum Dockerfile)"
update_pins 'loss of an existing platform blocks updates' 1 'update would remove platform linux/s390x'
[ "$before" = "$(sha256sum Dockerfile)" ]
unset TEST_CURRENT_MANIFEST
export TEST_CURRENT_MANIFEST='{"mediaType":"application/vnd.oci.image.index.v1+json","manifests":[]}'
update_pins 'an empty current index cannot silently lose platform guarantees' 1 'current pin has no runnable platforms'
[ "$before" = "$(sha256sum Dockerfile)" ]
export TEST_CURRENT_MANIFEST='invalid-json'
update_pins 'malformed current manifest does not edit files' 1 'Could not inspect current platforms'
[ "$before" = "$(sha256sum Dockerfile)" ]
unset TEST_CURRENT_MANIFEST

for failure in digest manifest; do
    export TEST_DOCKER_FAILURE="$failure"
    update_pins "failed ${failure} resolution does not edit files" 1 '[FAIL]'
    [ "$before" = "$(sha256sum Dockerfile)" ]
done
unset TEST_DOCKER_FAILURE
export TEST_TAG_MODE=failure
update_pins 'failed tag discovery does not edit files' 1 'Could not fetch Docker Hub tags'
[ "$before" = "$(sha256sum Dockerfile)" ]
export TEST_TAG_MODE=invalid
update_pins 'malformed tag discovery does not edit files' 1 'Invalid Docker Hub tag response'
[ "$before" = "$(sha256sum Dockerfile)" ]
export TEST_TAG_MODE=''
export TEST_DIGEST=invalid
update_pins 'invalid update digest does not edit files' 1 'Invalid index digest'
[ "$before" = "$(sha256sum Dockerfile)" ]
unset TEST_DIGEST
printf '{"mediaType":"application/vnd.oci.image.manifest.v1+json","config":{},"layers":[]}\n' > "$TEST_MANIFEST"
update_pins 'single-platform candidate is rejected without editing files' 1 'single platform manifest'
[ "$before" = "$(sha256sum Dockerfile)" ]

update_pins 'unknown mutation option is rejected' 64 'accepts neither options nor IMAGE' --unknown
IMAGE=test:1.0.0 update_pins 'IMAGE is not accepted for bulk mutation' 64 'accepts neither options nor IMAGE'
printf 'FROM test:1.0.0\n' > Dockerfile
update_pins 'unpinned images are not automatically pinned' 1 'No tagged digest pins found'
grep -Fxq 'FROM test:1.0.0' Dockerfile
