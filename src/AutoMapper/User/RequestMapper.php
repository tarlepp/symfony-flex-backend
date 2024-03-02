<?php
declare(strict_types = 1);
/**
 * /src/AutoMapper/User/RequestMapper.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\AutoMapper\User;

use App\AutoMapper\RestRequestMapper;
use App\Entity\UserGroup;
use App\Enum\Language;
use App\Enum\Locale;
use App\Resource\UserGroupResource;
use InvalidArgumentException;
use Throwable;
use function array_map;

/**
 * @package App\AutoMapper
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RequestMapper extends RestRequestMapper
{
    /**
     * @var array<int, non-empty-string>
     */
    protected static array $properties = [
        'username',
        'firstName',
        'lastName',
        'email',
        'language',
        'locale',
        'timezone',
        'userGroups',
        'password',
    ];

    public function __construct(
        private readonly UserGroupResource $userGroupResource,
    ) {
    }

    /**
     * @param array<int, string> $userGroups
     *
     * @return array<int, UserGroup>
     *
     * @throws Throwable
     */
    protected function transformUserGroups(array $userGroups): array
    {
        return array_map(
            fn (string $userGroupUuid): UserGroup => $this->userGroupResource->getReference($userGroupUuid),
            $userGroups,
        );
    }

    protected function transformLanguage(string $language): Language
    {
        return Language::tryFrom($language) ?? throw new InvalidArgumentException('Invalid language');
    }

    protected function transformLocale(string $locale): Locale
    {
        return Locale::tryFrom($locale) ?? throw new InvalidArgumentException('Invalid locale');
    }
}
