<?php

namespace AppWebSocket\Response\DataTransformer\Chat;

use AppWebSocket\Kernel\Response;
use Domain\Chat\Model\Chat;
use Domain\User\Model\User;
use AppWebSocket\Response\DataTransformer\DataTransformerInterface;

/**
 * Class ChatDataTransformer
 * @package AppWebSocket\Response\DataTransformer\Chat
 * @author Mykhailo YATSYHSYN <myyat@mirko.in.ua>
 * @copyright Mirko 1019-2020 <https://mirko.in.ua>
 */
class ChatDataTransformer implements DataTransformerInterface
{
    /**
     * @param array $object
     * @return string
     */
    public function getId($object): ?string
    {
        return ($object['chat']->id() === NULL) ? NULL : $object['chat']->id()->toString();
    }

    /**
     * @param array $object
     * @return array
     */
    public function getAttributes($object): array
    {
        /** @var Chat $chat */
        $chat = $object['chat'];
        $output =  [
            "chat" => [
                "id" => $chat->id() === NULL ? NULL : $chat->id()->toString(),
                "relateType" => $chat->getRelateType(),
                "relateId" => $chat->getRelateId(),
                "lastMessageTime" => $chat->getLastMessageTime() === NULL ? NULL : $chat->getLastMessageTime()->format("Y-m-d H:i:s")
            ]
        ];

        if(!empty($object['messages'])) {
            $output['lastMessages'] = Response::execCollectionDataTransformer(
                new ChatMessageDataTransformer(), $object['messages']
            );
        }

        return $output;
    }
}
