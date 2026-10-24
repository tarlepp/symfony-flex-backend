#!/bin/bash

set -euo pipefail

SCRIPT="$(cd "$(dirname "$0")/.." && pwd)/docker-images.sh"
WORK="$(mktemp -d)"

cleanup() {
    rm -f "$WORK/bin/docker" "$WORK/manifest.json" "$WORK/output" "$WORK/summary" \
        "$WORK/bin/curl" "$WORK/tags.json" "$WORK/requests" \
        "$WORK/Dockerfile" "$WORK/docker/nginx/Dockerfile" "$WORK/docker/mariadb/Dockerfile"
    rmdir "$WORK/docker/nginx" "$WORK/docker/mariadb" "$WORK/docker" "$WORK/bin" "$WORK"
}

trap cleanup EXIT

mkdir -p "$WORK/bin" "$WORK/docker/nginx" "$WORK/docker/mariadb"
cat > "$WORK/bin/docker" << 'EOF'
#!/bin/bash
set -euo pipefail
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
        cat "$TEST_MANIFEST"
        ;;
    "buildx imagetools inspect "*" --format {{.Manifest.Digest}}")
        if [ "${TEST_DOCKER_FAILURE:-}" = digest ]; then
            printf 'Digest lookup failed\n' >&2
            exit 1
        fi
        printf '%s\n' "${TEST_DIGEST:-sha256:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa}"
        ;;
    *) printf 'Unexpected Docker command: %s\n' "$*" >&2; exit 1 ;;
esac
EOF
chmod +x "$WORK/bin/docker"

export PATH="$WORK/bin:$PATH"
export TEST_MANIFEST="$WORK/manifest.json"
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

    printf 'PASS: %s (digest and verify)\n' "$name"
}

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

    : > "$TEST_REQUESTS"
    IMAGE="$image" bash "$SCRIPT" check-updates > "$WORK/output" 2>&1 || status=$?
    if [ "$status" -ne "$expected" ] || ! grep -Fq -- "$message" "$WORK/output"; then
        printf 'FAIL: %s: expected exit %s and message "%s", got exit %s\n' "$name" "$expected" "$message" "$status"
        cat "$WORK/output"
        exit 1
    fi
    printf 'PASS: %s\n' "$name"
}

check_updates 'stable major upgrade, excluding variants and prereleases' 'test:1.9.9@sha256:aaaa' 0 \
    '[UPDATE] test:1.9.9 -> test:2.0.0'
grep -Fxq 'IMAGE input (no matching Dockerfile)' "$WORK/output"
grep -Fq 'Multi-platform pin: test:2.0.0@sha256:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa' "$WORK/output"
grep -Fq 'Platforms: linux/amd64 linux/arm64/v8' "$WORK/output"
if grep -q $'\033' "$WORK/output"; then
    printf 'FAIL: NO_COLOR output contains ANSI escapes\n'
    exit 1
fi
NO_COLOR='' check_updates 'updates use yellow' 'test:1.9.9' 0 $'\033[1;33m[UPDATE]\033[0m'
NO_COLOR='' check_updates 'up-to-date images use green' 'test:2.0.0' 0 $'\033[0;32m[OK]\033[0m'
check_updates 'up-to-date image' 'test:2.0.0' 0 '[OK] test:2.0.0: no newer stable version'
if grep -q 'Multi-platform pin:' "$WORK/output"; then
    printf 'FAIL: up-to-date image printed an update pin\n'
    exit 1
fi
check_updates 'local version newer than registry' 'test:9.0.0' 0 'no newer stable version'
check_updates 'Docker Hub prefix and v prefix' 'docker.io/test:v1.0.0' 0 \
    'docker.io/test:v1.0.0 -> docker.io/test:v5.0.0'
grep -Fq '/repositories/library/test/tags/' "$TEST_REQUESTS"
check_updates 'namespaced repository' 'mlocati/test:1.0.0' 0 'mlocati/test:1.0.0 -> mlocati/test:2.0.0'
grep -Fq '/repositories/mlocati/test/tags/' "$TEST_REQUESTS"
check_updates 'unversioned image' 'test' 0 '[SKIP]'
check_updates 'floating version' 'test:1.9' 0 '[SKIP]'
check_updates 'prerelease version' 'test:1.9.9-rc1' 0 '[SKIP]'
[ ! -s "$TEST_REQUESTS" ]
check_updates 'unsupported registry' 'ghcr.io/owner/test:1.0.0' 1 'supports Docker Hub only'
check_updates 'registry with port' 'localhost:5000/test:1.0.0' 1 'supports Docker Hub only'
check_updates 'no matching variant' 'test:1.0.0-fpm-bookworm' 1 'no matching stable version tags found'

export TEST_TAG_MODE=pagination
check_updates 'all pages, numeric sorting and exact variant' 'test:1.9.9-fpm-bookworm' 0 \
    'test:1.9.9-fpm-bookworm -> test:10.0.0-fpm-bookworm'
[ "$(wc -l < "$TEST_REQUESTS")" -eq 2 ]
export TEST_TAG_MODE=cycle
check_updates 'multi-page pagination cycle' 'test:1.0.0' 1 'Repeated Docker Hub pagination URL'
[ "$(wc -l < "$TEST_REQUESTS")" -eq 3 ]
export TEST_TAG_MODE=failure
check_updates 'registry failure' 'test:1.0.0' 1 'Could not fetch Docker Hub tags'
export TEST_TAG_MODE=invalid
check_updates 'malformed registry response' 'test:1.0.0' 1 'Invalid Docker Hub tag response'
export TEST_TAG_MODE=''
printf '{"next":"https://example.com/other","results":[]}\n' > "$TEST_TAGS"
check_updates 'unexpected pagination host' 'test:1.0.0' 1 'Unexpected Docker Hub pagination URL'
printf '{"next":"https://hub.docker.com/v2/repositories/library/test/tags/?page_size=100","results":[]}\n' > "$TEST_TAGS"
check_updates 'repeated pagination page' 'test:1.0.0' 1 'Repeated Docker Hub pagination URL'

printf '{"next":null,"results":[{"name":"2.0.0"}]}\n' > "$TEST_TAGS"
cat > "$WORK/Dockerfile" << 'EOF'
FROM --platform=linux/amd64 test:1.0.0@sha256:aaaa AS base
COPY --from=tools/installer:1.0.0 /bin/tool /bin/tool
COPY --from=tools/installer:1.0.0 /bin/tool /bin/tool
FROM BASE AS development
COPY --from=base /bin/tool /bin/tool
COPY --from=0 /bin/tool /bin/tool
FROM scratch AS production
EOF
printf 'FROM test:1.0.0\n' > "$WORK/docker/nginx/Dockerfile"
printf 'FROM test:1.0.0\n' > "$WORK/docker/mariadb/Dockerfile"
check_updates 'default Dockerfile discovery, COPY tools and deduplication' '' 0 \
    'tools/installer:1.0.0 -> tools/installer:2.0.0'
[ "$(wc -l < "$TEST_REQUESTS")" -eq 2 ]
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
if grep -q 'SKIP\|ERROR' "$WORK/output"; then
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
check_updates 'multiline FROM and COPY with comments and blank lines' '' 0 \
    'tools/installer:1.0.0 -> tools/installer:2.0.0'
[ "$(wc -l < "$TEST_REQUESTS")" -eq 2 ]
grep -Fq 'test:1.0.0 -> test:2.0.0' "$WORK/output"
if grep -q 'ignored/\|SKIP\|ERROR' "$WORK/output"; then
    cat "$WORK/output"
    exit 1
fi

check_updates 'single image retains matching Dockerfiles' 'test:1.0.0' 0 \
    'docker/mariadb/Dockerfile'
grep -Fxq 'Dockerfile' "$WORK/output"
grep -Fxq 'docker/nginx/Dockerfile' "$WORK/output"
[ "$(wc -l < "$TEST_REQUESTS")" -eq 1 ]
if grep -q 'tools/installer' "$WORK/output"; then
    printf 'FAIL: single image filter included another image\n'
    exit 1
fi
for failure in digest manifest; do
    export TEST_DOCKER_FAILURE="$failure"
    check_updates "update ${failure} lookup failure" 'test:1.0.0' 1 'ERROR:'
    if grep -q 'Multi-platform pin:' "$WORK/output"; then
        printf 'FAIL: failed update printed a pin reference\n'
        exit 1
    fi
done
export TEST_DOCKER_FAILURE=''
export TEST_DIGEST=invalid
check_updates 'invalid update digest' 'test:1.0.0' 1 'Invalid index digest'
unset TEST_DIGEST
for manifest in \
    '{"mediaType":"application/vnd.oci.image.manifest.v1+json","config":{},"layers":[]}' \
    '{"mediaType":"application/vnd.oci.image.index.v1+json","manifests":[{"platform":{"os":"linux","architecture":"amd64"}},{"platform":{"os":"unknown","architecture":"unknown"}}]}' \
    '{"mediaType":"application/vnd.oci.image.index.v1+json","manifests":[]}' \
    'invalid-json'; do
    printf '%s\n' "$manifest" > "$TEST_MANIFEST"
    check_updates 'reject invalid or non-multi-platform update' 'test:1.0.0' 1 'ERROR:'
    if grep -q 'Multi-platform pin:' "$WORK/output"; then
        printf 'FAIL: rejected update printed a pin reference\n'
        exit 1
    fi
done
printf '{"mediaType":"application/vnd.docker.distribution.manifest.list.v2+json","manifests":[{"platform":{"os":"linux","architecture":"amd64"}},{"platform":{"os":"linux","architecture":"arm64","variant":"v8"}}]}\n' > "$TEST_MANIFEST"
check_updates 'Docker manifest list update pin' 'test:1.0.0' 0 \
    'Multi-platform pin: test:2.0.0@sha256:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'

printf 'FROM ghcr.io/owner/test:1.0.0\nCOPY --from=tools/installer:1.0.0 /bin/tool /bin/tool\n' > "$WORK/Dockerfile"
check_updates 'lookup errors do not prevent remaining checks' '' 1 \
    'tools/installer:1.0.0 -> tools/installer:2.0.0'
grep -Fq 'supports Docker Hub only' "$WORK/output"
for file in Dockerfile docker/nginx/Dockerfile docker/mariadb/Dockerfile; do
    printf 'FROM scratch\n' > "$WORK/$file"
done
check_updates 'empty image discovery fails explicitly' '' 1 'No external Dockerfile images found'
