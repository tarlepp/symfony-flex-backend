#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

fail() {
    echo "::error::Container diagnostics failed: $1" >&2
    exit 1
}

containers=$(timeout 10s docker compose -f compose.yaml -f docker/ci-compose.yaml \
    ps --all --quiet php 2>/dev/null) || fail "cannot list PHP containers."

if [[ -z "$containers" ]]; then
    echo "Container diagnostics: no PHP container exists."
    exit 0
fi

mapfile -t ids <<< "$containers"
(( ${#ids[@]} <= 5 )) || fail "too many PHP containers (limit 5)."
for id in "${ids[@]}"; do
    [[ "$id" =~ ^[a-f0-9]{12,64}$ ]] || fail "unexpected container identifier."
done

for id in "${ids[@]}"; do
    state=$(timeout 10s docker inspect --format \
        '{{.State.Status}} {{.State.ExitCode}} {{.State.OOMKilled}} {{if .State.Health}}{{.State.Health.Status}}{{else}}none{{end}}' \
        "$id" 2>/dev/null) || fail "cannot inspect PHP container state."
    [[ "$state" =~ ^(created|running|paused|restarting|removing|exited|dead)\ [0-9]{1,10}\ (true|false)\ (none|starting|healthy|unhealthy)$ ]] \
        || fail "unexpected container state."
    read -r status exit_code oom health <<< "$state"
    printf 'PHP container %.12s: status=%s exit=%s oom=%s health=%s\n' \
        "$id" "$status" "$exit_code" "$oom" "$health"
done
