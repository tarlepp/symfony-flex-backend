#
# Optionally load the repository Make wrapper in development Bash.
#
# Usage:
#   Source from the container's Bash startup configuration.
#
# Details:
#   The repository mount may be absent, so the wrapper is loaded only when its
#   file exists.
#
# Callers:
#   Docker:
#     - `Dockerfile` adds this file to the development user's `.bashrc`.
#

if [ -f /app/scripts/make-shell.sh ]; then
    source /app/scripts/make-shell.sh
fi
