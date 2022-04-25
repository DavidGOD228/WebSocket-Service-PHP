<?php

namespace AppWebSocket\Controller\Consultation;

use AppWebSocket\Kernel\Response;
use AppWebSocket\Response\DataTransformer\Chat\ChatMessageDataTransformer;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use Domain\Attachment\Model\Attachment;
use Domain\Chat\Model\Chat;
use Domain\Chat\Model\ChatMember;
use Domain\Chat\Model\ChatMessage;
use Domain\Chat\Service\MessageService;
use Domain\Consultation\Model\AdditionalMember;
use Domain\Consultation\Model\Consultation;
use Domain\Consultation\Repository\ConsultationRepositoryInterface;
use Domain\Guard\Service\GuardService;
use Domain\User\Model\Notification;
use Domain\User\Model\UserAttachment;
use Domain\User\Model\UserRole;
use Domain\User\ValueObject\UserId;
use AppWebSocket\Kernel\WsException;
use AppWebSocket\Kernel\UserManager;
use AppWebSocket\Kernel\ResponseCode;
use AppWebSocket\Controller\WsController;
use Domain\User\Repository\UserRepository;
use Infrastructure\Chat\Doctrine\Repository\ChatMemberRepository;
use Infrastructure\Chat\Doctrine\Repository\ChatMessageRepository;
use Infrastructure\Chat\Doctrine\Repository\ChatRepository;
use Infrastructure\Security\Service\TokenManager\AccessToken;
use Infrastructure\Security\Service\TokenManager\TokenManager;
use AppWebSocket\Response\DataTransformer\User\AuthDataTransformer;
use Infrastructure\SharedKernel\Exception\InvalidArgumentException;
use Infrastructure\User\Service\NotificationService;
use UserInterface\API\DataTransformer\Consultation\AdditionalMemberDataTransformer;
use UserInterface\API\DataTransformer\Consultation\ConsultationCallDataTransformer;
use UserInterface\API\DataTransformer\User\NotificationDataTransformer;

/**
 * Class CallController
 * @package AppWebSocket\Controller\Consultation
 * @author Mykhailo YATSYSHYN <myyat@mirko.in.ua>
 * @copyright Mirko 2019-2020 <https://mirko.in.ua>
 */
class CallWsController extends WsController
{
    const ROUTE = "Consultation\\Call";
    /**
     * @var ConsultationRepositoryInterface
     */
    private $consultationRepository;

    /**
     * @return array
     */
    public function access(): array
    {
        return [];
    }

    /**
     * @var ChatRepository
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
        $this->objectManager = $this->doctrine->getManager();
        $this->chatRepository = $this->doctrine->getRepository(Chat::class);
        $this->chatMemberRepository = $this->doctrine->getRepository(ChatMember::class);
        $this->chatMessageRepository = $this->doctrine->getRepository(ChatMessage::class);

        /** @var ConsultationRepositoryInterface $consultationRepository */
        $this->consultationRepository = $this->doctrine->getRepository(Consultation::class);
        $this->chatMessageRepository->injectConsultationRepository($this->consultationRepository);
    }

    /**
     * @param array $data
     * @return Response
     * @throws WsException
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     */
    public function callAction(?array $data)
    {
        if (empty($data['consultation'])) {
            throw new WsException("DATA: consultation in required", ResponseCode::APP_ERROR);
        }

        /** @var Chat $chat */
        $chat = $this->chatRepository->findOneBy([
            "relateId" => $data['consultation'],
            "relateType" => Chat::RELATE_TYPE_CONSULTATION
        ]);

        if($chat === NULL) {
            throw new WsException("Chat not found");
        }

        $members = $this->chatMemberRepository->getMembers($chat);

        /** @var UserManager $userManager */
        $userManager = $this->container->get(UserManager::class);

        $consultation = $this->consultationRepository->findOne($data['consultation']);

        $callData = [
            "consultation" => $consultation,
            "user" => $this->getWsUser()->getUser(),
        ];

        $responseData = Response::execDataTransformer(
            new ConsultationCallDataTransformer(), $callData
        );

        $users = [];
        foreach ($members as $chatMember) {
            $users[] = $chatMember->getUser();
        }

        $recipients = $userManager->getRecipientsByUsers($users);

        $response = Response::create(
            ResponseCode::NEW_MESSAGE,
            $responseData['id'],
            $responseData['attributes']
        );

        $response->setRoute(self::ROUTE);
        $response->setAction("call");

        $recipients->send($response);
    }

    /**
     * @param array $data
     * @return Response
     * @throws WsException
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     */
    public function cancelAction(?array $data) {
        if (empty($data['consultation'])) {
            throw new WsException("DATA: consultation in required", ResponseCode::APP_ERROR);
        }

        /** @var Chat $chat */
        $chat = $this->chatRepository->findOneBy([
            "relateId" => $data['consultation'],
            "relateType" => Chat::RELATE_TYPE_CONSULTATION
        ]);

        if($chat === NULL) {
            throw new WsException("Chat not found");
        }

        $members = $this->chatMemberRepository->getMembers($chat);

        /** @var UserManager $userManager */
        $userManager = $this->container->get(UserManager::class);

        $consultation = $this->consultationRepository->findOne($data['consultation']);

        $callData = [
            "consultation" => $consultation,
            "user" => $this->getWsUser()->getUser(),
        ];

        $responseData = Response::execDataTransformer(
            new ConsultationCallDataTransformer(), $callData
        );

        $users = [];
        foreach ($members as $chatMember) {
            $users[] = $chatMember->getUser();
        }

        $recipients = $userManager->getRecipientsByUsers($users);

        $response = Response::create(
            ResponseCode::NEW_MESSAGE,
            $responseData['id'],
            $responseData['attributes']
        );

        $response->setRoute(self::ROUTE);
        $response->setAction("cancel");

        $recipients->send($response);
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