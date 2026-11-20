#!/bin/bash

# Entry point for the read-only project statistics report.
# Delegates collection and output formatting to the adjacent Python
# implementation, preserving its exit status and command-line behavior.

set -euo pipefail

exec python3 "$(dirname "${BASH_SOURCE[0]}")/project-stats.py"
