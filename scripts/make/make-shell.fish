# Source this file to enable filtered Make output and target selection.
set -g SYMFONY_FISH_MAKE_ROOT (path resolve (path dirname (status filename))/../..)

function make
    if test "$PWD" = "$SYMFONY_FISH_MAKE_ROOT"
        command bash "$SYMFONY_FISH_MAKE_ROOT/scripts/make/make.sh" $argv
    else
        command make $argv
    end
end

function __symfony_make_targets
    if test "$PWD" = "$SYMFONY_FISH_MAKE_ROOT"
        bash "$SYMFONY_FISH_MAKE_ROOT/scripts/make/make-help.sh" --targets
    end
end

complete -c make -a '(__symfony_make_targets)'

function make-pick
    if test "$PWD" != "$SYMFONY_FISH_MAKE_ROOT"
        printf 'make-pick is available only from the repository root\n' >&2
        return 2
    end

    if not command -q fzf
        printf 'fzf is required for make-pick\n' >&2
        return 127
    end

    set -l records (__symfony_make_targets)
    or return $status
    set -l selected (printf '%s\n' $records |
        fzf --no-multi --prompt='make> ' --header='Enter runs the selected target' --height=80%)
    or return $status
    test (count $selected) -eq 1; or return 1
    set -l fields (string split -m 1 \t -- "$selected")
    test (count $fields) -eq 2; or return 1
    string match -qr '^[a-zA-Z0-9][a-zA-Z0-9-]*$' -- "$fields[1]"; or return 1

    make "$fields[1]"
end
