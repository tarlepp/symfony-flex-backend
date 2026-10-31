#!/usr/bin/env bash
set -euo pipefail

source "$(dirname "$0")/command-output.sh"

printf '%sAvailable commands%s\n' "$OUTPUT_BLUE" "$OUTPUT_RESET"
printf 'Run from the repository root. Application tooling forwards to the PHP container.\n'
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
' Makefile | sort | awk -F '\t' \
    -v blue="$OUTPUT_BLUE" -v green="$OUTPUT_GREEN" \
    -v yellow="$OUTPUT_YELLOW" -v reset="$OUTPUT_RESET" '
    BEGIN {
        groups["lifecycle"] = 1
        groups["docker"] = 2
        groups["setup"] = 3
        groups["tests"] = 4
        groups["checks"] = 5
        groups["quality"] = 6
        groups["information"] = 7
        groups["reports"] = 8
    }
    {
        if (!($3 in groups) || ($4 != "" && $4 != "DESTRUCTIVE" && $4 != "OVERWRITE")) {
            print "ERROR: Invalid help metadata: " $1 > "/dev/stderr"
            invalid = 1
            next
        }
        group = groups[$3]
        marker = $4 == "" ? "" : "[" $4 "] "
        entries[group] = entries[group] sprintf("  %s%-29s%s %s%s%s%s\n", green, $1, reset, yellow, marker, reset, $2)
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
        for (i = 1; i <= 8; i++)
            if (entries[i] != "") printf "%s%s%s\n%s\n", blue, titles[i], reset, entries[i]
    }
'
