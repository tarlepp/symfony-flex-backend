<?php
declare(strict_types = 1);
/**
 * /src/Command/HelperConfigure.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Command;

use Closure;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\Console\Completion\Suggestion;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use function array_map;

/**
 * @psalm-type TInputOption=array{
 *      name: string,
 *      shortcut?: string,
 *      mode?: int-mask-of<InputOption::*>,
 *      description?: string,
 *      default?: scalar|array<array-key, mixed>,
 *      suggestedValues?: array<array-key, mixed>
 *           |Closure(CompletionInput,CompletionSuggestions):list<string|Suggestion>,
 *  }
 *
 * @package App\Command
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class HelperConfigure
{
    /**
     * @param list<TInputOption> $parameters
     */
    public static function configure(Command $command, array $parameters): void
    {
        // Configure command
        $command->setDefinition(new InputDefinition(array_map(self::getParameterIterator(), $parameters)));
    }

    /**
     * @return Closure(TInputOption):InputOption
     */
    private static function getParameterIterator(): Closure
    {
        return static fn (array $input): InputOption => new InputOption(
            $input['name'],
            $input['shortcut'] ?? null,
            $input['mode'] ?? InputOption::VALUE_OPTIONAL,
            $input['description'] ?? '',
            $input['default'] ?? null,
            $input['suggestedValues'] ?? [],
        );
    }
}
