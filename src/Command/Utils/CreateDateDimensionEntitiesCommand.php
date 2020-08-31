<?php
declare(strict_types = 1);
/**
 * /src/Command/Utils/CreateDateDimensionEntitiesCommand.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Command\Utils;

use App\Entity\DateDimension;
use App\Repository\DateDimensionRepository;
use Closure;
use DateInterval;
use DateTime;
use DateTimeZone;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use function sprintf;

/**
 * Class CreateDateDimensionEntitiesCommand
 *
 * @package App\Command\Utils
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class CreateDateDimensionEntitiesCommand extends Command
{
    private const YEAR_MIN = 1970;
    // This should be the year when I'm officially retired
    private const YEAR_MAX = 2047;

    /**
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private SymfonyStyle $io;
    private DateDimensionRepository $repository;

    /**
     * PopulateDateDimensionCommand constructor.
     */
    public function __construct(DateDimensionRepository $dateDimensionRepository)
    {
        parent::__construct('utils:create-date-dimension-entities');

        $this->repository = $dateDimensionRepository;

        $this->setDescription('Console command to create \'DateDimension\' entities.');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * {@inheritdoc}
     *
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Create output decorator helpers for the Symfony Style Guide.
        $this->io = new SymfonyStyle($input, $output);

        // Set title
        $this->io->title($this->getDescription());

        // Determine start and end years
        $yearStart = $this->getYearStart();
        $yearEnd = $this->getYearEnd($yearStart);

        // Create actual entities
        $this->process($yearStart, $yearEnd);

        $this->io->success('All done - have a nice day!');

        return 0;
    }

    /**
     * Method to get start year value from user.
     *
     * @throws InvalidArgumentException
     */
    private function getYearStart(): int
    {
        return (int)$this->io->ask('Give a year where to start', (string)self::YEAR_MIN, $this->validatorYearStart());
    }

    /**
     * Method to get end year value from user.
     *
     * @throws InvalidArgumentException
     */
    private function getYearEnd(int $yearStart): int
    {
        return (int)$this->io->ask(
            'Give a year where to end',
            (string)self::YEAR_MAX,
            $this->validatorYearEnd($yearStart)
        );
    }

    /**
     * Method to create DateDimension entities to database.
     *
     * @throws Throwable
     */
    private function process(int $yearStart, int $yearEnd): void
    {
        $dateStart = new DateTime($yearStart . '-01-01 00:00:00', new DateTimeZone('UTC'));
        $dateEnd = new DateTime($yearEnd . '-12-31 00:00:00', new DateTimeZone('UTC'));

        $progress = $this->getProgressBar(
            (int)$dateEnd->diff($dateStart)->format('%a') + 1,
            sprintf('Creating DateDimension entities between years %d and %d...', $yearStart, $yearEnd)
        );

        // Remove existing entities
        $this->repository->reset();

        // Create entities to database
        $this->createEntities($yearEnd, $dateStart, $progress);
    }

    /**
     * Helper method to get progress bar for console.
     */
    private function getProgressBar(int $steps, string $message): ProgressBar
    {
        $format = '
 %message%
 %current%/%max% [%bar%] %percent:3s%%
 Time elapsed:   %elapsed:-6s%
 Time remaining: %remaining:-6s%
 Time estimated: %estimated:-6s%
 Memory usage:   %memory:-6s%
';

        $progress = $this->io->createProgressBar($steps);
        $progress->setFormat($format);
        $progress->setMessage($message);

        return $progress;
    }

    /**
     * @throws Throwable
     */
    private function createEntities(int $yearEnd, DateTime $dateStart, ProgressBar $progress): void
    {
        // Get entity manager for _fast_ database handling.
        $em = $this->repository->getEntityManager();

        // You spin me round (like a record... er like a date)
        while ((int)$dateStart->format('Y') < $yearEnd + 1) {
            $em->persist(new DateDimension(clone $dateStart));

            $dateStart->add(new DateInterval('P1D'));

            // Flush in 1000 batches to database
            if ($progress->getProgress() % 1000 === 0) {
                $em->flush();
                $em->clear();
            }

            $progress->advance();
        }

        // Finally flush remaining entities
        $em->flush();
        $em->clear();
    }

    /**
     * Getter method for year start validator closure.
     *
     * @throws InvalidArgumentException
     */
    private function validatorYearStart(): Closure
    {
        return static function (int $year): int {
            if ($year < self::YEAR_MIN || $year > self::YEAR_MAX) {
                $message = sprintf(
                    'Start year must be between %d and %d',
                    self::YEAR_MIN,
                    self::YEAR_MAX
                );

                throw new InvalidArgumentException($message);
            }

            return $year;
        };
    }

    /**
     * Getter method for year end validator closure.
     *
     * @throws InvalidArgumentException
     */
    private function validatorYearEnd(int $yearStart): Closure
    {
        return static function (int $year) use ($yearStart): int {
            if ($year < self::YEAR_MIN || $year > self::YEAR_MAX || $year < $yearStart) {
                $message = sprintf(
                    'End year must be between %d and %d and after given start year %d',
                    self::YEAR_MIN,
                    self::YEAR_MAX,
                    $yearStart
                );

                throw new InvalidArgumentException($message);
            }

            return $year;
        };
    }
}
