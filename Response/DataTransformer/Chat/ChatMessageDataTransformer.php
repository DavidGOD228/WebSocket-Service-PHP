<?php

namespace AppWebSocket\Response\DataTransformer\Chat;

use AppWebSocket\Kernel\Response;
use Domain\Chat\Model\Chat;
use Domain\Chat\Model\ChatMessage;
use Domain\Chat\Service\MessageService;
use Domain\User\Model\User;
use AppWebSocket\Response\DataTransformer\DataTransformerInterface;

/**
 * Class ChatDataTransformer
 * @package AppWebSocket\Response\DataTransformer\Chat
 * @author Mykhailo YATSYHSYN <myyat@mirko.in.ua>
 * @copyright Mirko 1019-2020 <https://mirko.in.ua>
 */
class ChatMessageDataTransformer implements DataTransformerInterface
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
        $service = new MessageService();

        $output =  [
            "id" => ($object->id() === NULL) ? NULL : $object->id()->toString(),
            "chat_id" => $object->getChat() === NULL ? NULL : $object->getChat()->id()->toString(),
            "message" => $service->responseMessageByType($object->getMessage(), $object->getType()),
            "type" => $object->getType(),
            "sender" => $object->getSender()->id()->toString(),
            "date" => $object->getDate()->format("Y-m-d H:i:s"),
            "read" => $object->getStatusRead()
        ];

        unset($service);

        return $output;
    }
}
