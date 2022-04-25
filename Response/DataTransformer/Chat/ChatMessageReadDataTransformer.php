<?php

namespace AppWebSocket\Response\DataTransformer\Chat;

use AppWebSocket\Kernel\Response;
use Domain\Chat\Model\Chat;
use Domain\Chat\Model\ChatMessage;
use Domain\Chat\Service\MessageService;
use Domain\User\Model\User;
use AppWebSocket\Response\DataTransformer\DataTransformerInterface;

/**
 * Class ChatMessageReadDataTransformer
 * @package AppWebSocket\Response\DataTransformer\Chat
 * @author Mykhailo YATSYHSYN <myyat@mirko.in.ua>
 * @copyright Mirko 1019-2020 <https://mirko.in.ua>
 */
class ChatMessageReadDataTransformer implements DataTransformerInterface
{
    /**
     * @param ChatMessage $object
     * @return string
     */
    public function getId($object): ?string
    {
        return ($object->id() === NULL) ? NULL : $object->id()->toString();
    }

    /**
     * @param ChatMessage $object
     * @return array
     */
    public function getAttributes($object): array
    {
        return  [
            "id" => ($object->id() === NULL) ? NULL : $object->id()->toString(),
            "chat_id" => $object->getChat() === NULL ? NULL : $object->getChat()->id()->toString(),
            "read" => $object->getStatusRead()
        ];
    }
}
