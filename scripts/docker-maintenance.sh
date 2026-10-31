#!/usr/bin/env bash
set -euo pipefail

source "$(dirname "$0")/command-output.sh"

action=${1:-}
case "$action" in
    kill|prune) query=(ps -q); command=(kill); label='Kill Docker containers' ;;
    containers) query=(ps -a -q); command=(rm); label='Remove Docker containers' ;;
    images) query=(images -q); command=(rmi); label='Remove Docker images' ;;
    *) output_status FAIL 'Usage: docker-maintenance.sh <kill|containers|images|prune>'; exit 64 ;;
esac

output_status WARN 'This operation affects ALL Docker resources on this host, including unrelated projects'
if [ "$action" = prune ]; then
    output_status WARN 'Killing running containers and pruning unused images, networks, build cache and eligible volumes; data may be lost'
    printf 'Kill ALL running containers and prune Docker data? [y/N] ' >&2
    if ! read -r answer || [ "$answer" != y ]; then
        output_result SKIP 'Docker system prune cancelled; nothing changed'
        exit 0
    fi
fi

if ids=$(docker "${query[@]}"); then
    resources=()
    while IFS= read -r id; do
        [ -z "$id" ] || resources+=("$id")
    done <<< "$ids"
else
    status=$?
    output_result FAIL "Could not list Docker resources (exit $status)"
    exit "$status"
fi

if [ "$action" = prune ]; then
    if [ "${#resources[@]}" -gt 0 ]; then
        if docker kill "${resources[@]}"; then
            :
        else
            status=$?
            output_result FAIL "Could not kill containers; prune was not run (exit $status)"
            exit "$status"
        fi
    fi
    OUTPUT_SUCCESS_MESSAGE='Docker system prune completed' \
        output_run 'Prune Docker system resources' docker system prune --all --volumes --force
elif [ "${#resources[@]}" -eq 0 ]; then
    output_result SKIP 'No matching Docker resources; nothing changed'
else
    output_run "$label" docker "${command[@]}" "${resources[@]}"
fi
