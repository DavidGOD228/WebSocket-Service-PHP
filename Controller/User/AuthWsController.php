<?php

namespace AppWebSocket\Controller\User;

use AppWebSocket\Kernel\Response;
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

/**
 * Class AuthWsController
 * @package AppWebSocket\Controller\User
 * @author Mykhailo YATSYHSYN <myyat@mirko.in.ua>
 * @copyright Mirko 1019-2020 <https://mirko.in.ua>
 */
class AuthWsController extends WsController
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
    public function authorizationAction(?array $data)
    {
        if(!isset($data['token'])) {
            throw new WsException("DATA: token is required", ResponseCode::APP_ERROR);
        }

        $token = $data['token'];

        /** @var TokenManager $tokenManager */
        $tokenManager = $this->container->get(TokenManager::class);

        try {
            /** @var AccessToken $credential */
            $credential = $tokenManager->decoding($token);
        } catch (InvalidArgumentException $exception) {
            throw new WsException($exception->getMessage(), ResponseCode::ACCESS_DENIED);
        }

        /** @var UserManager $userManager */
        $userManager = $this->container->get(UserManager::class);

        /** @var UserRepository $userRepository */
        $userRepository = $this->container->get(UserRepository::class);
        $user = $userRepository->getById(UserId::fromString($credential->getId()));

        $userManager->authUserConnection($this->getWsUser(), $user);

        return $this->response(new AuthDataTransformer, $user);
    }
}