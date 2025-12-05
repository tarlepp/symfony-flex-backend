<?php
declare(strict_types = 1);
/**
 * /src/Entity/Traits/LogEntityTrait.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Entity\Traits;

use App\Entity\User;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * @package App\Entity\Traits
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @property User|null $user
 */
trait LogEntityTrait
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
    protected DateTimeImmutable $time;

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
    protected DateTimeImmutable $date;

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
    protected string $agent = '';

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
    protected string $httpHost = '';

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
    private string $clientIp = '';

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

    private function processRequestData(Request $request): void
    {
        $this->clientIp = $request->getClientIp() ?? '';
        $this->httpHost = $request->getHttpHost();
        $this->agent = $request->headers->get('User-Agent') ?? '';
    }

    #[ORM\PrePersist]
    private function processTimeAndDate(): void
    {
        $now = new DateTimeImmutable(timezone: new DateTimeZone('UTC'));

        $this->time = $now;
        $this->date = $now;
    }
}
