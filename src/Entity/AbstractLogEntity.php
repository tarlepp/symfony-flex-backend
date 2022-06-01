<?php
declare(strict_types = 1);
/**
 * /src/Entity/AbstractLogEntity.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Entity;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Annotation\Groups;
use Throwable;

/**
 * Class AbstractLogEntity
 *
 * @package App\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[ORM\MappedSuperclass]
abstract class AbstractLogEntity
{
    #[ORM\Column(
        name: 'time',
        type: Types::DATETIME_IMMUTABLE,
        nullable: false,
    )]
    #[Groups([
        'LogLogin',
        'LogLogin.time',
        'LogRequest',
        'LogRequest.time',
    ])]
    protected readonly DateTimeImmutable $time;

    #[ORM\Column(
        name: '`date`',
        type: Types::DATE_IMMUTABLE,
        nullable: false,
    )]
    #[Groups([
        'LogLogin',
        'LogLogin.date',
        'LogRequest',
        'LogRequest.date',
    ])]
    protected readonly DateTimeImmutable $date;

    #[ORM\Column(
        name: 'agent',
        type: Types::TEXT,
        nullable: false,
    )]
    #[Groups([
        'LogLogin',
        'LogLogin.agent',
        'LogRequest',
        'LogRequest.agent',
    ])]
    protected readonly string $agent;

    #[ORM\Column(
        name: 'http_host',
        type: Types::STRING,
        length: 255,
        nullable: false,
    )]
    #[Groups([
        'LogLogin',
        'LogLogin.httpHost',
        'LogRequest',
        'LogRequest.httpHost',
    ])]
    protected readonly string $httpHost;

    #[ORM\Column(
        name: 'client_ip',
        type: Types::STRING,
        length: 255,
        nullable: false,
    )]
    #[Groups([
        'LogLogin',
        'LogLogin.clientIp',
        'LogRequest',
        'LogRequest.clientIp',
    ])]
    protected readonly string $clientIp;

    /**
     * @throws Throwable
     */
    public function __construct(?Request $request)
    {
        $now = new DateTimeImmutable(timezone: new DateTimeZone('UTC'));

        $this->time = $now;
        $this->date = $now;
        $this->clientIp = $request?->getClientIp() ?? '';
        $this->httpHost = $request?->getHttpHost() ?? '';
        $this->agent = $request?->headers->get('User-Agent') ?? '';
    }

    public function getTime(): DateTimeImmutable
    {
        return $this->time;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function getAgent(): string
    {
        return $this->agent;
    }

    public function getHttpHost(): string
    {
        return $this->httpHost;
    }

    public function getClientIp(): string
    {
        return $this->clientIp;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->getDate();
    }
}
