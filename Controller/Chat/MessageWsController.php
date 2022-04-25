<?php

namespace AppWebSocket\Controller\Chat;

use AppWebSocket\Controller\WsController;
use AppWebSocket\Kernel\RecipientCollection;
use AppWebSocket\Kernel\Response;
use AppWebSocket\Kernel\ResponseCode;
use AppWebSocket\Kernel\UserManager;
use AppWebSocket\Kernel\WsException;
use AppWebSocket\Response\DataTransformer\Chat\ChatDataTransformer;
use AppWebSocket\Response\DataTransformer\Chat\ChatMessageDataTransformer;
use AppWebSocket\Response\DataTransformer\Chat\ChatMessageReadDataTransformer;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityRepository;
use Domain\Chat\Model\Chat;
use Domain\Chat\Model\ChatMember;
use Domain\Chat\Model\ChatMessage;
use Domain\Chat\Service\MessageService;
use Domain\Chat\ValueObject\ChatId;
use Domain\Consultation\Model\Consultation;
use Domain\Consultation\Repository\ConsultationRepositoryInterface;
use Domain\Consultation\ValueObject\ConsultationId;
use Domain\Guard\Service\GuardService;
use Domain\User\Model\UserRole;
use Infrastructure\Chat\Doctrine\Repository\ChatMemberRepository;
use Infrastructure\Chat\Doctrine\Repository\ChatMessageRepository;
use Infrastructure\Consultation\Doctrine\Repository\ConsultationRepository;

/**
 * Class MessageWsController
 * @package AppWebSocket\Controller\Chat
 * @author Mykhailo YATSYHSYN <myyat@mirko.in.ua>
 * @copyright Mirko 1019-2020 <https://mirko.in.ua>
 */
class MessageWsController extends WsController
{
    /**
     * @var MessageService
     */
    private $messageService;

    /**
     * @var EntityRepository
     */
    private $chatRepository;

    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var ChatMemberRepository
     */
    private $chatMemberRepository;
    /**
     * @var ChatMessageRepository
     */
    private $chatMessageRepository;

    /**
     * __Construct
     */
    public function construct()
    {
        $this->messageService = new MessageService();
        $this->objectManager = $this->doctrine->getManager();
        $this->chatRepository = $this->doctrine->getRepository(Chat::class);
        $this->chatMemberRepository = $this->doctrine->getRepository(ChatMember::class);
        $this->chatMessageRepository = $this->doctrine->getRepository(ChatMessage::class);

        /** @var ConsultationRepositoryInterface $consultationRepository */
        $consultationRepository = $this->doctrine->getRepository(Consultation::class);
        $this->chatMessageRepository->injectConsultationRepository($consultationRepository);
    }

    /**
     * @return array
     */
    public function access(): array
    {
        return  [
            "chatConnect" => [
                UserRole::PATIENT, UserRole::MODERATOR, UserRole::DOCTOR
            ],
            'send' => [
                UserRole::PATIENT, UserRole::MODERATOR, UserRole::DOCTOR
            ]
        ];
    }

    /**
     * @param array $data
     * @throws WsException
     *
     * @throws \Domain\Guard\Exception\GuardException
     */
    public function sendAction(array $data)
    {
        $wsUser = $this->getWsUser();
        $messageRequest = $this->messageService->validateMessageRequest($data);

        $chatId = (int)$messageRequest['chat_id'];

        $chat = $this->getChat($chatId);

        $chatMessage = new ChatMessage();

        $chatMessage->setSender($wsUser->getUser());
        $chatMessage->setChat($chat);

        $messageType = $this->messageService->getMessageType($messageRequest['type']);
        $messageType->build($chatMessage, $messageRequest);

        $chat->setLastMessageTime($chatMessage->getDate());

        $chatMessage = $this->chatMessageRepository->addMessage($chat, $chatMessage);


        // Build message content
        $responseData = Response::execDataTransformer(
            new ChatMessageDataTransformer(), $chatMessage
        );

        /** @var UserManager $userManager */
        $userManager = $this->container->get(UserManager::class);

        // Get WS Connection for members in online
        $users = [];
        $chatMembers = $this->chatMemberRepository->getMembers($chat);
        /** @var ChatMember $chatMember */
        foreach ($chatMembers as $chatMember) {
            $users[] = $chatMember->getUser();
        }
        $recipients = $userManager->getRecipientsByUsers($users);

        // Build all body response
        $response = Response::create(
            ResponseCode::NEW_MESSAGE,
            $responseData['id'],
            $responseData['attributes'],
        );

        $response->setRoute("Chat\\Message");
        $response->setAction("MessageReceived");

        // Send messages and clean objects
        $recipients->send($response);

        unset($response);
        unset($recipients);
        unset($responseData);
        unset($chatMessage);
    }

    /**
     * @param array $data
     *
     * @return \AppWebSocket\Kernel\Response
     *
     * @throws WsException
     * @throws \Domain\Guard\Exception\GuardException
     */
    public function chatConnectAction(array $data)
    {
        if(!isset($data['type']) || !isset($data['id'])) {
            throw new WsException("Arguments: type and id is required", ResponseCode::APP_ERROR);
        }

        $messageService = new MessageService();
        $doctrine = $this->container->get("doctrine");
        $chatRepository = $doctrine->getRepository(Chat::class);
        /** @var ChatMessageRepository $chatMessageRepository */
        $chatMessageRepository = $doctrine->getRepository(ChatMessage::class);

        /** @var Chat $chat */
        $chat = $chatRepository->findOneBy([
            "relateType" => $data['type'],
            "relateId" => $data['id']
        ]);

        $messages = [];
        if($chat === NULL) {
            throw new WsException("Chat not found ");
        }

        // Check access
        $chatMembers = $this->chatMemberRepository->getMembers($chat);
        GuardService::chatMember($chatMembers,  $this->getWsUser()->getUser());
        $messages = $chatMessageRepository->getMessages($chat, [
            "limit" => ChatMessageRepository::DEFAULT_MESSAGE_LIMIT,
        ]);

        return $this->response(new ChatDataTransformer, [
            'chat' => $chat,
            "messages" => $messages
        ]);
    }

    /**
     * @param array $data
     *
     * @return Response
     *
     * @throws WsException
     * @throws \Domain\Guard\Exception\GuardException
     */
    public function loadMessageAction(array $data)
    {
        $chatId = (int)$data['chat_id'];
        $lastMessageId = (int)$data['last'];
        $chat = $this->getChat($chatId);

        $messages = $this->chatMessageRepository->loadMessages($chat, $lastMessageId);

        return $this->responseCollection(new ChatMessageDataTransformer(), $messages);
    }

    /**
     * @param array $data
     *
     * @throws WsException
     *
     * @throws \Domain\Guard\Exception\GuardException
     */
    public function readAction(array $data)
    {
        $chatId = (int)$data['chat_id'];
        $messageIDS = (array)$data['messages'];

        $chat = $this->getChat($chatId);

        $messages = $this->chatMessageRepository->getMessages($chat, [
            "ids" => $messageIDS
        ]);

        foreach ($messages as $message) {
            if($message->getStatusRead()) {
                continue;
            }

            $message->setStatusRead(ChatMessage::READ_STATUS_TRUE);
            $this->objectManager->persist($message);
        }

        $this->objectManager->flush();

        // Build message content
        $responseData = Response::execCollectionDataTransformer(
            new ChatMessageDataTransformer(), $messages
        );

        /** @var UserManager $userManager */
        $userManager = $this->container->get(UserManager::class);

        // Get WS Connection for members in online
        $users = [];
        $chatMembers = $this->chatMemberRepository->getMembers($chat);
        /** @var ChatMember $chatMember */
        foreach ($chatMembers as $chatMember) {
            $users[] = $chatMember->getUser();
        }
        $recipients = $userManager->getRecipientsByUsers($users);

        // Build all body response
        $response = $this->responseCollection(
            new ChatMessageReadDataTransformer(),
            $messages,
            ResponseCode::NEW_MESSAGE
        );

        $response->setRoute("Chat\\Message");
        $response->setAction("MessageRead");

        // Send messages and clean objects
        $recipients->send($response);

        unset($response);
        unset($recipients);
        unset($responseData);
        unset($chatMessage);

        unset($messages);
        unset($chat);
    }

    /**
     * @param array $data
     * @throws WsException
     * @throws \Domain\Guard\Exception\GuardException
     */
    public function newMessageAction(array $data)
    {
        $chatId = (int)$data['chat_id'];
        $chat = $this->getChat($chatId);

        /** @var UserManager $userManager */
        $userManager = $this->container->get(UserManager::class);

        // Get WS Connection for members in online
        $users = [];
        $chatMembers = $this->chatMemberRepository->getMembers($chat);
        /** @var ChatMember $chatMember */
        foreach ($chatMembers as $chatMember) {
            $users[] = $chatMember->getUser();
        }
        $recipients = $userManager->getRecipientsByUsers($users);
        $response = $this->response(
            new ChatDataTransformer(),
            ['chat' => $chat],
            ResponseCode::NEW_MESSAGE
        );

        $response->setRoute("Chat\\Message");
        $response->setAction("ChatReload");

        // Send messages and clean objects
        $recipients->send($response);

        unset($response);
        unset($recipients);
        unset($responseData);
        unset($chatMessage);

        unset($messages);
        unset($chat);
    }

    /**
     * @param int $chatId
     * @return Chat
     * @throws WsException
     * @throws \Domain\Guard\Exception\GuardException
     */
    private function getChat(int $chatId)
    {
        $wsUser = $this->getWsUser();

        /** @var Chat $chat */
        $chat = $this->chatRepository->find($chatId);

        if($chat === NULL) {
            throw new WsException("Bad Request: chat by id <{$chatId}> not found");
        }

        // Check access
        $chatMembers = $this->chatMemberRepository->getMembers($chat);
        GuardService::chatMember($chatMembers, $wsUser->getUser());

        return $chat;
    }
}