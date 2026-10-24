#!/bin/bash

set -euo pipefail

SCRIPT="$(cd "$(dirname "$0")/.." && pwd)/docker-images.sh"
WORK="$(mktemp -d)"

cleanup() {
    rm -f "$WORK/bin/docker" "$WORK/manifest.json" "$WORK/output" "$WORK/summary" \
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
    "buildx imagetools inspect --raw "*) cat "$TEST_MANIFEST" ;;
    "buildx imagetools inspect "*" --format {{.Manifest.Digest}}")
        printf 'sha256:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa\n'
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
