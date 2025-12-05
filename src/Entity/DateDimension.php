<?php
declare(strict_types = 1);
/**
 * /src/Entity/DateDimension.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Entity;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\Traits\Uuid;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Override;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Throwable;
use function floor;

/**
 * @package App\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[ORM\Entity(
    readOnly: true,
)]
#[ORM\Table(
    name: 'date_dimension',
)]
#[ORM\Index(
    columns: [
        'date',
    ],
    name: 'date',
)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class DateDimension implements EntityInterface
{
    use Uuid;

    #[ORM\Id]
    #[ORM\Column(
        name: 'id',
        type: UuidBinaryOrderedTimeType::NAME,
        unique: true,
    )]
    #[Groups([
        'DateDimension',
        'DateDimension.id',
    ])]
    #[OA\Property(type: 'string', format: 'uuid')]
    private UuidInterface $id;

    #[ORM\Column(
        name: 'year',
        type: Types::INTEGER,
        options: [
            'comment' => 'A full numeric representation of a year, 4 digits',
        ],
    )]
    #[Groups([
        'DateDimension',
        'DateDimension.year',
    ])]
    private int $year;

    #[ORM\Column(
        name: 'month',
        type: Types::INTEGER,
        options: [
            'comment' => 'Day of the month without leading zeros; 1 to 12',
        ],
    )]
    #[Groups([
        'DateDimension',
        'DateDimension.month',
    ])]
    private int $month;

    #[ORM\Column(
        name: 'day',
        type: Types::INTEGER,
        options: [
            'comment' => 'Day of the month without leading zeros; 1 to 31',
        ],
    )]
    #[Groups([
        'DateDimension',
        'DateDimension.day',
    ])]
    private int $day;

    #[ORM\Column(
        name: 'quarter',
        type: Types::INTEGER,
        options: [
            'comment' => 'Calendar quarter; 1, 2, 3 or 4',
        ],
    )]
    #[Groups([
        'DateDimension',
        'DateDimension.quarter',
    ])]
    private int $quarter;

    #[ORM\Column(
        name: 'week_number',
        type: Types::INTEGER,
        options: [
            'comment' => 'ISO-8601 week number of year, weeks starting on Monday',
        ],
    )]
    #[Groups([
        'DateDimension',
        'DateDimension.weekNumber',
    ])]
    private int $weekNumber;

    #[ORM\Column(
        name: 'day_number_of_week',
        type: Types::INTEGER,
        options: [
            'comment' => 'ISO-8601 numeric representation of the day of the week; 1 (for Monday) to 7 (for Sunday)',
        ],
    )]
    #[Groups([
        'DateDimension',
        'DateDimension.dayNumber',
    ])]
    private int $dayNumberOfWeek;

    #[ORM\Column(
        name: 'day_number_of_year',
        type: Types::INTEGER,
        options: [
            'comment' => 'The day of the year (starting from 0); 0 through 365',
        ],
    )]
    #[Groups([
        'DateDimension',
        'DateDimension.dayNumberOfYear',
    ])]
    private int $dayNumberOfYear;

    #[ORM\Column(
        name: 'leap_year',
        type: Types::BOOLEAN,
        options: [
            'comment' => 'Whether it\'s a leap year or not',
        ],
    )]
    #[Groups([
        'DateDimension',
        'DateDimension.leapYear',
    ])]
    private bool $leapYear;

    #[ORM\Column(
        name: 'week_numbering_year',
        type: Types::INTEGER,
        options: [
            'comment' => 'ISO-8601 week-numbering year.',
        ],
    )]
    #[Groups([
        'DateDimension',
        'DateDimension.weekNumberingYear',
    ])]
    private int $weekNumberingYear;

    #[ORM\Column(
        name: 'unix_time',
        type: Types::BIGINT,
        options: [
            'comment' => 'Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT)',
        ],
    )]
    #[Groups([
        'DateDimension',
        'DateDimension.unixTime',
    ])]
    private string $unixTime;

    public function __construct(
        #[ORM\Column(
            name: 'date',
            type: Types::DATE_IMMUTABLE,
        )]
        #[Groups([
            'DateDimension',
            'DateDimension.date',
        ])]
        private readonly DateTimeImmutable $date
    ) {
        $this->id = $this->createUuid();

        $this->year = (int)$date->format('Y');
        $this->month = (int)$date->format('n');
        $this->day = (int)$date->format('j');
        $this->quarter = (int)floor(((int)$date->format('n') - 1) / 3) + 1;
        $this->weekNumber = (int)$date->format('W');
        $this->dayNumberOfWeek = (int)$date->format('N');
        $this->dayNumberOfYear = (int)$date->format('z');
        $this->leapYear = (bool)$date->format('L');
        $this->weekNumberingYear = (int)$date->format('o');
        $this->unixTime = $date->format('U');
    }

    #[Override]
    public function getId(): string
    {
        return $this->id->toString();
    }

    public function getDate(): DateTimeInterface
    {
        return $this->date;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getMonth(): int
    {
        return $this->month;
    }

    public function getDay(): int
    {
        return $this->day;
    }

    public function getQuarter(): int
    {
        return $this->quarter;
    }

    public function getWeekNumber(): int
    {
        return $this->weekNumber;
    }

    public function getDayNumberOfWeek(): int
    {
        return $this->dayNumberOfWeek;
    }

    public function getDayNumberOfYear(): int
    {
        return $this->dayNumberOfYear;
    }

    public function isLeapYear(): bool
    {
        return $this->leapYear;
    }

    public function getWeekNumberingYear(): int
    {
        return $this->weekNumberingYear;
    }

    public function getUnixTime(): string
    {
        return $this->unixTime;
    }

    /**
     * @throws Throwable
     */
    #[Override]
    public function getCreatedAt(): DateTimeImmutable
    {
        $output = DateTimeImmutable::createFromFormat('U', $this->getUnixTime(), new DateTimeZone('UTC'));

        return $output === false ? new DateTimeImmutable(timezone: new DateTimeZone('UTC')) : $output;
    }
}
