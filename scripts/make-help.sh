#!/usr/bin/env bash
set -euo pipefail

source "$(dirname "$0")/command-output.sh"

printf '%sAvailable commands%s\n' "$OUTPUT_BLUE" "$OUTPUT_RESET"
printf 'Run from the repository root. Application tooling forwards to the PHP container.\n'
printf '[DESTRUCTIVE] deletes/stops resources; [OVERWRITE] replaces files or tags.\n\n'

awk '
    /^[a-zA-Z0-9-]+:.*## / {
        name = $0; sub(/:.*/, "", name)
        description = $0; sub(/^.*## /, "", description)
        print name "\t" description
    }
' Makefile | sort | awk -F '\t' \
    -v blue="$OUTPUT_BLUE" -v green="$OUTPUT_GREEN" \
    -v yellow="$OUTPUT_YELLOW" -v reset="$OUTPUT_RESET" '
    function category(name) {
        if (name ~ /^(bash|fish|logs|start.*|daemon.*|stop|ci-start|ci-stop)$/) return 1
        if (name ~ /^docker-/) return 2
        if (name ~ /^(install.*|update.*|clear.*|cache-.*|local-configuration|configure-ci|generate-.*)$/) return 3
        if (name ~ /^(run-tests.*|infection|test-.*)$/) return 4
        if (name ~ /^(check-dependencies-.*|check-action-updates|check-security|check-licenses)$/) return 5
        if (name ~ /^(configuration|project-stats|application-info|tool-versions)$/) return 7
        if (name ~ /^(report-.*|prepare-.*|phpmetrics|phploc)$/) return 8
        return 6
    }
    function marker(name) {
        if (name ~ /^(clear.*|cache-clear|cache-warmup|stop|ci-stop|docker-prune|docker-kill-containers|docker-remove-.*)$/)
            return "[DESTRUCTIVE] "
        if (name ~ /^(local-configuration|configure-ci|generate-.*|ecs-fix|composer-normalize|docker-build-.*|prepare-sonar-reports)$/)
            return "[OVERWRITE] "
        return ""
    }
    {
        group = category($1)
        entries[group] = entries[group] sprintf("  %s%-29s%s %s%s%s%s\n", green, $1, reset, yellow, marker($1), reset, $2)
    }
    END {
        titles[1] = "Docker lifecycle and interactive sessions (host)"
        titles[2] = "Docker images and host maintenance (host)"
        titles[3] = "Setup, dependencies and application maintenance"
        titles[4] = "Application tests and tooling regressions"
        titles[5] = "Dependency, security and license checks"
        titles[6] = "Linting and static analysis"
        titles[7] = "Configuration and information"
        titles[8] = "Reports and uploads"
        for (i = 1; i <= 8; i++)
            if (entries[i] != "") printf "%s%s%s\n%s\n", blue, titles[i], reset, entries[i]
    }
'
