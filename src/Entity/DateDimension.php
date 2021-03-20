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
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Throwable;
use function floor;

/**
 * Class DateDimension
 *
 * @ORM\Table(
 *      name="date_dimension",
 *      indexes={
 *          @ORM\Index(name="date", columns={"date"}),
 *      },
 *  )
 * @ORM\Entity(
 *      readOnly=true,
 *  )
 *
 * @package App\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class DateDimension implements EntityInterface
{
    use Uuid;

    /**
     * @ORM\Column(
     *      name="id",
     *      type="uuid_binary_ordered_time",
     *      unique=true,
     *      nullable=false,
     *  )
     * @ORM\Id()
     *
     * @OA\Property(type="string", format="uuid")
     */
    #[Groups([
        'DateDimension',
        'DateDimension.id',
    ])]
    private UuidInterface $id;

    /**
     * @ORM\Column(
     *      name="date",
     *      type="date",
     *      nullable=false,
     *  )
     */
    #[Groups([
        'DateDimension',
        'DateDimension.date',
    ])]
    private DateTime $date;

    /**
     * @ORM\Column(
     *      name="year",
     *      type="integer",
     *      nullable=false,
     *      options={
     *          "comment": "A full numeric representation of a year, 4 digits",
     *      },
     *  )
     */
    #[Groups([
        'DateDimension',
        'DateDimension.year',
    ])]
    private int $year;

    /**
     * @ORM\Column(
     *      name="month",
     *      type="integer",
     *      nullable=false,
     *      options={
     *          "comment": "Day of the month without leading zeros; 1 to 12",
     *      },
     *  )
     */
    #[Groups([
        'DateDimension',
        'DateDimension.month',
    ])]
    private int $month;

    /**
     * @ORM\Column(
     *      name="day",
     *      type="integer",
     *      nullable=false,
     *      options={
     *          "comment": "Day of the month without leading zeros; 1 to 31",
     *      },
     *  )
     */
    #[Groups([
        'DateDimension',
        'DateDimension.day',
    ])]
    private int $day;

    /**
     * @ORM\Column(
     *      name="quarter",
     *      type="integer",
     *      nullable=false,
     *      options={
     *          "comment": "Calendar quarter; 1, 2, 3 or 4",
     *      },
     *  )
     */
    #[Groups([
        'DateDimension',
        'DateDimension.quarter',
    ])]
    private int $quarter;

    /**
     * @ORM\Column(
     *      name="week_number",
     *      type="integer",
     *      nullable=false,
     *      options={
     *          "comment": "ISO-8601 week number of year, weeks starting on Monday",
     *      },
     *  )
     */
    #[Groups([
        'DateDimension',
        'DateDimension.weekNumber',
    ])]
    private int $weekNumber;

    /**
     * @ORM\Column(
     *      name="day_number_of_week",
     *      type="integer",
     *      nullable=false,
     *      options={
     *          "comment": "ISO-8601 numeric representation of the day of the week; 1 (for Monday) to 7 (for Sunday)",
     *      },
     *  )
     */
    #[Groups([
        'DateDimension',
        'DateDimension.dayNumber',
    ])]
    private int $dayNumberOfWeek;

    /**
     * @ORM\Column(
     *      name="day_number_of_year",
     *      type="integer",
     *      nullable=false,
     *      options={
     *          "comment": "The day of the year (starting from 0); 0 through 365",
     *      },
     *  )
     */
    #[Groups([
        'DateDimension',
        'DateDimension.dayNumberOfYear',
    ])]
    private int $dayNumberOfYear;

    /**
     * @ORM\Column(
     *      name="leap_year",
     *      type="boolean",
     *      nullable=false,
     *      options={
     *          "comment": "Whether it's a leap year",
     *      },
     *  )
     */
    #[Groups([
        'DateDimension',
        'DateDimension.leapYear',
    ])]
    private bool $leapYear;

    /**
     * @ORM\Column(
     *      name="week_numbering_year",
     *      type="integer",
     *      nullable=false,
     *      options={
     *          "comment": "ISO-8601 week-numbering year.",
     *      },
     *  )
     */
    #[Groups([
        'DateDimension',
        'DateDimension.weekNumberingYear',
    ])]
    private int $weekNumberingYear;

    /**
     * @ORM\Column(
     *      name="unix_time",
     *      type="bigint",
     *      nullable=false,
     *      options={
     *          "comment": "Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT)",
     *      },
     *  )
     */
    #[Groups([
        'DateDimension',
        'DateDimension.unixTime',
    ])]
    private int $unixTime;

    public function __construct(DateTime $dateTime)
    {
        $this->id = $this->createUuid();

        $this->date = $dateTime;
        $this->year = (int)$dateTime->format('Y');
        $this->month = (int)$dateTime->format('n');
        $this->day = (int)$dateTime->format('j');
        $this->quarter = (int)floor(((int)$dateTime->format('n') - 1) / 3) + 1;
        $this->weekNumber = (int)$dateTime->format('W');
        $this->dayNumberOfWeek = (int)$dateTime->format('N');
        $this->dayNumberOfYear = (int)$dateTime->format('z');
        $this->leapYear = (bool)$dateTime->format('L');
        $this->weekNumberingYear = (int)$dateTime->format('o');
        $this->unixTime = (int)$dateTime->format('U');
    }

    public function getId(): string
    {
        return $this->id->toString();
    }

    public function getDate(): DateTime
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

    public function getUnixTime(): int
    {
        return $this->unixTime;
    }

    /**
     * @throws Throwable
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        $output = DateTimeImmutable::createFromFormat('U', (string)$this->getUnixTime(), new DateTimeZone('UTC'));

        return $output === false ? new DateTimeImmutable(timezone: new DateTimeZone('UTC')) : $output;
    }
}
