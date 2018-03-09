<?php
declare(strict_types = 1);
/**
 * /src/Entity/Traits/UserSerializer.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Entity\Traits;

use App\Utils\JSON;
use LogicException;

/**
 * Trait UserSerializer
 *
 * @package App\Entity\Traits
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @property string $wtf // for some reason we need this bogus property before actual properties...
 * @property string $id
 * @property string $username
 * @property string $password
 */
trait UserSerializer
{
    /**
     * String representation of object
     *
     * @return string the string representation of the object
     *
     * @throws LogicException
     */
    public function serialize(): string
    {
        return JSON::encode([
            'id' => $this->id,
            'username' => $this->username,
            'password' => $this->password,
        ]);
    }

    /**
     * Constructs the object
     *
     * @param string $serialized The string representation of the object.
     *
     * @throws LogicException
     */
    public function unserialize($serialized): void
    {
        $data = JSON::decode($serialized);

        $this->id = $data->id;
        $this->username = $data->username;
        $this->password = $data->password;
    }
}
