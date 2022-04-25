<?php

namespace AppWebSocket\Kernel;

use Ratchet\ConnectionInterface;

/**
 * Class RecipientCollection
 * @package AppWebSocket\Kernel
 * @author Mykhailo YATSYHSYN <myyat@mirko.in.ua>
 * @copyright Mirko 1019-2020 <https://mirko.in.ua>
 */
class RecipientCollection
{
    private $recipients = [];

    /**
     * @param ConnectionInterface $connection
     */
    public function add(ConnectionInterface $connection): void
    {
        $this->recipients[] = $connection;
    }

    /**
     * @return ConnectionInterface[]
     */
    public function all(): array
    {
        return $this->recipients;
    }

    /**
     * @param WsUser[] $connections\
     */
    public function addList(array $connections): void
    {
        foreach ($connections as $connection) {
            $this->add($connection->getConnection());
        }
    }

    /**
     * @param Response $response
     */
    public function send(Response $response)
    {
        $data = $response->prepareForSend();

        foreach ($this->all() as $connection) {
            $connection->send($data);
        }
    }
}