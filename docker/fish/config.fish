source /home/dev/.config/fish/completions/sf_console.fish
thefuck --alias | source
if status is-interactive
    fzf_key_bindings
end
if test -f /app/scripts/make-shell.fish
    source /app/scripts/make-shell.fish
end
