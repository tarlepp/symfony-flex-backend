source /home/dev/.config/fish/completions/sf_console.fish
thefuck --alias | source
if test -f /app/scripts/make-shell.fish
    source /app/scripts/make-shell.fish
end
