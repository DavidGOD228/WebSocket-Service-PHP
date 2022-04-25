<?php

namespace AppWebSocket\Controller\User;

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
use UserInterface\API\DataTransformer\User\NotificationDataTransformer;

/**
 * Class NotificationWsController
 * @package AppWebSocket\Controller\User
 * @author Mykhailo YATSYHSYN <myyat@mirko.in.ua>
 * @copyright Mirko 2019-2020 <https://mirko.in.ua>
 */
class NotificationWsController extends WsController
{
    /**
     * @return array
     */
    public function access(): array
    {
        return  [];
    }

    /**
     * @param array $data
     * @return Response
     * @throws WsException
     */
    public function sendAction(?array $data)
    {
        if(!isset($data['secretToken'])) {
            throw new WsException("DATA: token is required", ResponseCode::APP_ERROR);
        }

        if($data['secretToken'] !== NotificationService::SECRET_KEY) {
            throw new WsException("DATA: token is invalid", ResponseCode::APP_ERROR);
        }

        if(empty($data['ids'])) {
            throw new WsException("DATA: ids in required", ResponseCode::APP_ERROR);
        }

        $notificationRepositories = $this->doctrine->getRepository(Notification::class);

        $this->clearDoctrineCache([
            ChatMember::class,
            AdditionalMember::class,
            Notification::class
        ]);

        /** @var UserManager $userManager */
        $userManager = $this->container->get(UserManager::class);

        $allNotifications = [];

        sleep(5);
        foreach ($data['ids'] as $id) {
            echo "note: ".$id.PHP_EOL;
            /** @var Notification $notification */
            $notification = $notificationRepositories->findOneBy([
                "id" => $id
            ]);

            if($notification === NULL) {
                continue;
            }

            $allNotifications[] = $notification;
            $responseData = Response::execDataTransformer(
                new NotificationDataTransformer(), $notification
            );
            $recipients = $userManager->getRecipientsByUsers([$notification->user()]);

            $response = Response::create(
                ResponseCode::NEW_MESSAGE,
                $responseData['id'],
                $responseData['attributes']
            );

            $response->setRoute("User\\Notification");
            $response->setAction("send");

            $recipients->send($response);
        }

        return $this->responseCollection(new NotificationDataTransformer(), $allNotifications);
    }
}