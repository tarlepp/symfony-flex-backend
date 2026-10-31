#!/usr/bin/env bash
set -euo pipefail

source "$(dirname "$0")/command-output.sh"

group=${1:-all}
if [ "$#" -gt 1 ] || [[ ! "$group" =~ ^(all|information|tests|quality|checks|reports|setup|lifecycle|docker)$ ]]; then
    output_status FAIL "Unknown help group: $group"
    output_status INFO 'Use GROUP=all|information|tests|quality|checks|reports|setup|lifecycle|docker'
    exit 64
fi

printf '%sAvailable commands%s\n' "$OUTPUT_BLUE" "$OUTPUT_RESET"
printf 'Run from the repository root. Application tooling forwards to the PHP container.\n'
printf 'Filter: make help GROUP=<group> (default: all)\n'
printf 'Groups: information, tests, quality, checks, reports, setup, lifecycle, docker\n'
printf 'Routine commands first; overwrite and destructive commands last in each group.\n'
printf '[DESTRUCTIVE] deletes/stops resources; [OVERWRITE] replaces files or tags.\n\n'

awk '
    /^\$\(call REGISTER_TARGET,/ {
        record = $0
        sub(/^\$\(call REGISTER_TARGET,/, "", record)
        sub(/\)$/, "", record)
        split(record, fields, ",")
        name = fields[1]
        if (name in groups) {
            print "ERROR: Duplicate target metadata: " name > "/dev/stderr"
            invalid = 1
        }
        groups[name] = fields[2]
        risks[name] = fields[4]
    }
    /^[a-zA-Z0-9-]+:.*## / {
        name = $0; sub(/:.*/, "", name)
        description = $0; sub(/^.*## /, "", description)
        descriptions[name] = description
    }
    END {
        for (name in descriptions) {
            if (!(name in groups) || groups[name] == "hidden") {
                print "ERROR: Missing help metadata: " name > "/dev/stderr"
                invalid = 1
            }
        }
        for (name in groups) {
            if (groups[name] != "hidden" && !(name in descriptions)) {
                print "ERROR: Missing target description: " name > "/dev/stderr"
                invalid = 1
            }
        }
        if (invalid) exit 1
        for (name in descriptions)
            print name "\t" descriptions[name] "\t" groups[name] "\t" risks[name]
    }
' Makefile | LC_ALL=C sort -t $'\t' -k1,1 | awk -F '\t' \
    -v blue="$OUTPUT_BLUE" -v green="$OUTPUT_GREEN" \
    -v yellow="$OUTPUT_YELLOW" -v reset="$OUTPUT_RESET" -v selected="$group" '
    BEGIN {
        groups["lifecycle"] = 1
        groups["docker"] = 2
        groups["setup"] = 3
        groups["tests"] = 4
        groups["checks"] = 5
        groups["quality"] = 6
        groups["information"] = 7
        groups["reports"] = 8
        order[1] = 7
        order[2] = 4
        order[3] = 6
        order[4] = 5
        order[5] = 8
        order[6] = 3
        order[7] = 1
        order[8] = 2
    }
    {
        if (!($3 in groups) || ($4 != "" && $4 != "DESTRUCTIVE" && $4 != "OVERWRITE")) {
            print "ERROR: Invalid help metadata: " $1 > "/dev/stderr"
            invalid = 1
            next
        }
        group = groups[$3]
        if (selected != "all" && selected != $3) next
        risk = $4 == "DESTRUCTIVE" ? 2 : ($4 == "OVERWRITE" ? 1 : 0)
        marker = $4 == "" ? "" : "[" $4 "] "
        entries[group, risk] = entries[group, risk] sprintf("  %s%-29s%s %s%s%s%s\n", green, $1, reset, yellow, marker, reset, $2)
        populated[group] = 1
    }
    END {
        if (invalid) exit 1
        titles[1] = "Docker lifecycle and interactive sessions (host)"
        titles[2] = "Docker images and host maintenance (host)"
        titles[3] = "Setup, dependencies and application maintenance"
        titles[4] = "Application tests and tooling regressions"
        titles[5] = "Dependency, security and license checks"
        titles[6] = "Linting and static analysis"
        titles[7] = "Configuration and information"
        titles[8] = "Reports and uploads"
        for (i = 1; i <= 8; i++) {
            group = order[i]
            if (populated[group]) {
                printf "%s%s%s\n", blue, titles[group], reset
                for (risk = 0; risk <= 2; risk++) printf "%s", entries[group, risk]
                printf "\n"
            }
        }
    }
'
