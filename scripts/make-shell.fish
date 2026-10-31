# Source this file to enable filtered Make output in this repository.
set -g SYMFONY_FISH_MAKE_ROOT (path resolve (path dirname (status filename))/..)

function make
    if test "$PWD" = "$SYMFONY_FISH_MAKE_ROOT"
        command bash "$SYMFONY_FISH_MAKE_ROOT/scripts/make.sh" $argv
    else
        command make $argv
    end
end
