# Optional Bash startup integration for the development container.
# Source this file from the container's Bash configuration; the repository may
# be absent, so load its Make wrapper only when the mounted file is available.

if [ -f /app/scripts/make-shell.sh ]; then
    source /app/scripts/make-shell.sh
fi
