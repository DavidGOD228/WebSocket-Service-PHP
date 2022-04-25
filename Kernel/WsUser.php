<?php

namespace AppWebSocket\Kernel;

use Domain\User\Model\User;
use Ratchet\ConnectionInterface;

/**
 * Class WsUser
 * @package AppWebSocket\Kernel
 * @author Mykhailo YATSYHSYN <myyat@mirko.in.ua>
 * @copyright Mirko 1019-2020 <https://mirko.in.ua>
 */
class WsUser
{
    /**
     * @var User
     */
    private $user;

    /**
     * @var string
     */
    private $role;

    /**
     * @var int
     */
    private $resourceId;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var bool
     */
    private $isAuth = false;

    /**
     * WsUser constructor.
     * @param ConnectionInterface $connection
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->resourceId = $connection->resourceId;
    }

    /**
     * @param User $user
     */
    public function auth(User $user)
    {
        $roles = $user->getRoles();
        $this->role = end($roles);
        $this->user = $user;
    }

    /**
     * Return status auth user
     *
     * @return bool
     */
    public function isAuth(): bool
    {
        return $this->isAuth;
    }

    /**
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * @return ConnectionInterface
     */
    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * @return int
     */
    public function getResourceId(): int
    {
        return $this->resourceId;
    }

    /**
     * @return User
     */
    public function getUser(): ?User
    {
        return $this->user;
    }
}