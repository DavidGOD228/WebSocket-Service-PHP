<?php

namespace AppWebSocket\Response\DataTransformer\User;

use Domain\User\Model\User;
use AppWebSocket\Response\DataTransformer\DataTransformerInterface;

/**
 * Class AuthDataTransformer
 * @package AppWebSocket\Response\DataTransformer\User
 * @author Mykhailo YATSYHSYN <myyat@mirko.in.ua>
 * @copyright Mirko 1019-2020 <https://mirko.in.ua>
 */
class AuthDataTransformer implements DataTransformerInterface
{
    /**
     * @param User $object
     * @return string
     */
    public function getId($object): ?string
    {
        return ($object === NULL) ? NULL : $object->id()->toString();
    }

    /**
     * @param User $object
     * @return array
     */
    public function getAttributes($object): array
    {
        return [
            'auth' => ($object instanceof User)
        ];
    }
}
