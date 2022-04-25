<?php

namespace AppWebSocket\Kernel;

use Domain\User\Model\User;
use Ratchet\ConnectionInterface;

/**
 * Class UserManager
 * @package AppWebSocket\Kernel
 * @author Mykhailo YATSYHSYN <myyat@mirko.in.ua>
 * @copyright Mirko 1019-2020 <https://mirko.in.ua>
 */
class UserManager
{
    private $userConnections = [];
    /**
     * @var WsUser[]
     */
    private $connections = [];

    /**
     * @param ConnectionInterface $connection
     */
    public function addConnection(ConnectionInterface $connection)
    {
        $wsUser = new WsUser($connection);
        $this->connections[$wsUser->getResourceId()] = $wsUser;

    }

    /**
     * @param WsUser $wsUser
     * @param User $user
     */
    public function authUserConnection(WsUser $wsUser, User $user)
    {
        $wsUser->auth($user);
        $this->userConnections[$user->id()->toString()][$wsUser->getResourceId()] = $wsUser->getResourceId();
    }


    /**
     * @param ConnectionInterface $connection
     *
     * @return WsUser
     *
     * @throws WsException
     */
    public function getByConnection(ConnectionInterface $connection): WsUser
    {
        $resourceID = $connection->resourceId;
        if(!isset($this->connections[$resourceID])) {
            throw new WsException(
                "Kernel Error: connection (Resource id {$resourceID}) not have WsUser.",
                ResponseCode::KERNEL_ERROR
            );
        }

        return $this->connections[$resourceID];
    }

    /**
     * @param ConnectionInterface $connection
     *
     * @return bool
     *
     * @throws WsException
     */
    public function closeConnection(ConnectionInterface $connection)
    {
        $wsUser = $this->getByConnection($connection);
        $wsUsers = $this->getWsUserConnections($wsUser);
        $resourceID = $wsUser->getResourceId();
        if(count($wsUsers) > 1) {
            unset($this->userConnections[$wsUser->getUser()->id()->toString()][$resourceID]);
        }

        unset($this->connections[$resourceID]);
        unset($wsUser);
        $connection->close();

        return true;
    }

    /**
     * @param WsUser $wsUser
     * @return WsUser[]
     */
    public function getWsUserConnections(WsUser $wsUser)
    {
        if($wsUser->isAuth()) {
            return $this->userConnections[$wsUser->getUser()->id()->toString()];
        }

        return [$wsUser];
    }

    /**
     * @param int $resourceId
     * @return WsUser|null
     */
    public function getConnectionByResourceId(int $resourceId): ?WsUser
    {
        if(!isset($this->connections[$resourceId])) {
            return NULL;
        }

        return $this->connections[$resourceId];
    }


    /**
     * @param array $resourceIds
     * @return WsUser[]
     */
    public function getConnectionsByResourceId(array $resourceIds): array
    {
        $connections = [];
        foreach ($resourceIds as $resourceId) {
            $connection =  $this->getConnectionByResourceId($resourceId);
            if($connection === NULL) {
                continue;
            }

            $connections[] = $connection;
        }

        return $connections;
    }

    /**
     * @param User[] $users
     * @return RecipientCollection
     */
    public function getRecipientsByUsers(array $users): RecipientCollection
    {
        $recipients = new RecipientCollection();

        foreach ($users as $user) {
            $connections = $this->getUserConnections($user);
            $recipients->addList($connections);
        }

        return $recipients;
    }

    /**
     * @param User $user
     * @return WsUser[]
     */
    public function getUserConnections(User $user)
    {
        $connections = [];
        $userId = $user->id()->toString();
        if(!empty($this->userConnections[$userId])) {
            $connections = $this->getConnectionsByResourceId($this->userConnections[$userId]);
        }

        return $connections;
    }
}