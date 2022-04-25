<?php

namespace AppWebSocket\Controller\Consultation;

use AppWebSocket\Kernel\Response;
use AppWebSocket\Response\DataTransformer\Chat\ChatMessageDataTransformer;
use Domain\Attachment\Model\Attachment;
use Domain\Chat\Model\ChatMember;
use Domain\Consultation\Model\AdditionalMember;
use Domain\User\Model\Notification;
use Domain\User\Model\UserAttachment;
use Domain\User\Model\UserRole;
use Domain\User\ValueObject\UserId;
use AppWebSocket\Kernel\WsException;
use AppWebSocket\Kernel\UserManager;
use AppWebSocket\Kernel\ResponseCode;
use AppWebSocket\Controller\WsController;
use Domain\User\Repository\UserRepository;
use Infrastructure\Security\Service\TokenManager\AccessToken;
use Infrastructure\Security\Service\TokenManager\TokenManager;
use AppWebSocket\Response\DataTransformer\User\AuthDataTransformer;
use Infrastructure\SharedKernel\Exception\InvalidArgumentException;
use Infrastructure\User\Service\NotificationService;
use UserInterface\API\DataTransformer\Consultation\AdditionalMemberDataTransformer;
use UserInterface\API\DataTransformer\User\NotificationDataTransformer;

/**
 * Class NotificationWsController
 * @package AppWebSocket\Controller\User
 * @author Mykhailo YATSYHSYN <myyat@mirko.in.ua>
 * @copyright Mirko 2019-2020 <https://mirko.in.ua>
 */
class DoctorWsController extends WsController
{
    const ROUTE = "Consultation\\Doctor";

    /**
     * @return array
     */
    public function access(): array
    {
        return [];
    }

    /**
     * @param array $data
     * @return Response
     * @throws WsException
     */
    public function joinNoteAction(?array $data)
    {
        if (!isset($data['secretToken'])) {
            throw new WsException("DATA: token is required", ResponseCode::APP_ERROR);
        }

        if ($data['secretToken'] !== NotificationService::SECRET_KEY) {
            throw new WsException("DATA: token is invalid", ResponseCode::APP_ERROR);
        }

        if (empty($data['memberId'])) {
            throw new WsException("DATA: id in required", ResponseCode::APP_ERROR);
        }

        $memberRepository = $this->doctrine->getRepository(AdditionalMember::class);

        $this->clearDoctrineCache([
            ChatMember::class,
            AdditionalMember::class
        ]);

        /** @var UserManager $userManager */
        $userManager = $this->container->get(UserManager::class);

        sleep(1);
        /** @var AdditionalMember $member */
        $member = $memberRepository->findOneBy([
            "id" => $data['memberId']
        ]);

        if ($member === NULL) {
            throw new WsException("Member not found");
        }

        $responseData = Response::execDataTransformer(
            new AdditionalMemberDataTransformer(), $member
        );
        $recipients = $userManager->getRecipientsByUsers([
            $member->consultation()->doctor()->user(),
            $member->consultation()->patient()->user(),
        ]);

        $response = Response::create(
            ResponseCode::NEW_MESSAGE,
            $responseData['id'],
            $responseData['attributes']
        );

        $response->setRoute("Consultation\\Doctor");
        $response->setAction("AdditionalDoctorJoin");

        $recipients->send($response);

        return $this->response(new AdditionalMemberDataTransformer(), $member);
    }
}