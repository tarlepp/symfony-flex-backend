<?php
declare(strict_types = 1);
/**
 * /src/Entity/DateDimension.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Entity;

use App\Entity\Traits\Uuid;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
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
 *      }
 *  )
 * @ORM\Entity(
 *      readOnly=true
 *  )
 *
 * @package App\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class DateDimension implements EntityInterface
{
    // Traits
    use Uuid;

    /**
     * @var string
     *
     * @Groups({
     *      "DateDimension",
     *      "DateDimension.id",
     *  })
     *
     * @ORM\Column(
     *      name="id",
     *      type="uuid_binary_ordered_time",
     *      unique=true,
     *      nullable=false,
     *  )
     * @ORM\Id()
     */
    private $id;

    /**
     * @var DateTime
     *
     * @Groups({
     *      "DateDimension",
     *      "DateDimension.date",
     *  })
     *
     * @ORM\Column(
     *      name="date",
     *      type="date",
     *      nullable=false,
     *  )
     */
    private $date;

    /**
     * @var int
     *
     * @Groups({
     *      "DateDimension",
     *      "DateDimension.year",
     *  })
     *
     * @ORM\Column(
     *      name="year",
     *      type="integer",
     *      nullable=false,
     *      options={
     *          "comment": "A full numeric representation of a year, 4 digits",
     *      },
     *  )
     */
    private $year;

    /**
     * @var int
     *
     * @Groups({
     *      "DateDimension",
     *      "DateDimension.month",
     *  })
     *
     * @ORM\Column(
     *      name="month",
     *      type="integer",
     *      nullable=false,
     *      options={
     *          "comment": "Day of the month without leading zeros; 1 to 12",
     *      },
     *  )
     */
    private $month;

    /**
     * @var int
     *
     * @Groups({
     *      "DateDimension",
     *      "DateDimension.day",
     *  })
     *
     * @ORM\Column(
     *      name="day",
     *      type="integer",
     *      nullable=false,
     *      options={
     *          "comment": "Day of the month without leading zeros; 1 to 31",
     *      },
     *  )
     */
    private $day;

    /**
     * @var int
     *
     * @Groups({
     *      "DateDimension",
     *      "DateDimension.quarter",
     *  })
     *
     * @ORM\Column(
     *      name="quarter",
     *      type="integer",
     *      nullable=false,
     *      options={
     *          "comment": "Calendar quarter; 1, 2, 3 or 4",
     *      },
     *  )
     */
    private $quarter;

    /**
     * @var int
     *
     * @Groups({
     *      "DateDimension",
     *      "DateDimension.weekNumber",
     *  })
     *
     * @ORM\Column(
     *      name="week_number",
     *      type="integer",
     *      nullable=false,
     *      options={
     *          "comment": "ISO-8601 week number of year, weeks starting on Monday",
     *      },
     *  )
     */
    private $weekNumber;

    /**
     * @var int
     *
     * @Groups({
     *      "DateDimension",
     *      "DateDimension.dayNumber",
     *  })
     *
     * @ORM\Column(
     *      name="day_number_of_week",
     *      type="integer",
     *      nullable=false,
     *      options={
     *          "comment": "ISO-8601 numeric representation of the day of the week; 1 (for Monday) to 7 (for Sunday)",
     *      },
     *  )
     */
    private $dayNumberOfWeek;

    /**
     * @var int
     *
     * @Groups({
     *      "DateDimension",
     *      "DateDimension.dayNumberOfYear",
     *  })
     *
     * @ORM\Column(
     *      name="day_number_of_year",
     *      type="integer",
     *      nullable=false,
     *      options={
     *          "comment": "The day of the year (starting from 0); 0 through 365",
     *      },
     *  )
     */
    private $dayNumberOfYear;

    /**
     * @var bool
     *
     * @Groups({
     *      "DateDimension",
     *      "DateDimension.leapYear",
     *  })
     *
     * @ORM\Column(
     *      name="leap_year",
     *      type="boolean",
     *      nullable=false,
     *      options={
     *          "comment": "Whether it's a leap year",
     *      },
     *  )
     */
    private $leapYear;

    /**
     * @var int
     *
     * @Groups({
     *      "DateDimension",
     *      "DateDimension.weekNumberingYear",
     *  })
     *
     * @ORM\Column(
     *      name="week_numbering_year",
     *      type="integer",
     *      nullable=false,
     *      options={
     *          "comment": "ISO-8601 week-numbering year.",
     *      },
     *  )
     */
    private $weekNumberingYear;

    /**
     * @var int
     *
     * @Groups({
     *      "Default",
     *      "DateDimension",
     *      "DateDimension.unixTime",
     *  })
     *
     * @ORM\Column(
     *      name="unix_time",
     *      type="bigint",
     *      nullable=false,
     *      options={
     *          "comment": "Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT)",
     *      },
     *  )
     */
    private $unixTime;

    /**
     * DateDimension constructor.
     *
     * @param DateTime|null $dateTime
     *
     * @throws Throwable
     */
    public function __construct(?DateTime $dateTime = null)
    {
        $this->id = $this->getUuid();

        if ($dateTime !== null) {
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
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id->toString();
    }

    /**
     * @return DateTime
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * @return int
     */
    public function getYear(): int
    {
        return $this->year;
    }

    /**
     * @return int
     */
    public function getMonth(): int
    {
        return $this->month;
    }

    /**
     * @return int
     */
    public function getDay(): int
    {
        return $this->day;
    }

    /**
     * @return int
     */
    public function getQuarter(): int
    {
        return $this->quarter;
    }

    /**
     * @return int
     */
    public function getWeekNumber(): int
    {
        return $this->weekNumber;
    }

    /**
     * @return int
     */
    public function getDayNumberOfWeek(): int
    {
        return $this->dayNumberOfWeek;
    }

    /**
     * @return int
     */
    public function getDayNumberOfYear(): int
    {
        return $this->dayNumberOfYear;
    }

    /**
     * @return bool
     */
    public function isLeapYear(): bool
    {
        return $this->leapYear;
    }

    /**
     * @return int
     */
    public function getWeekNumberingYear(): int
    {
        return $this->weekNumberingYear;
    }

    /**
     * @return int
     */
    public function getUnixTime(): int
    {
        return $this->unixTime;
    }

    /**
     * Returns createdAt.
     *
     * @return DateTimeImmutable
     *
     * @throws Throwable
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        $output = DateTimeImmutable::createFromFormat('U', (string)$this->getUnixTime(), new DateTimeZone('UTC'));

        return $output === false ? new DateTimeImmutable('now', new DateTimeZone('UTC')) : $output;
    }
}
