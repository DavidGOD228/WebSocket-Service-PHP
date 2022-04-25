<?php

namespace AppWebSocket\Controller\Server;

use AppWebSocket\Controller\WsController;
use Domain\User\Model\UserRole;
use Ratchet\Server\IoServer;

/**
 * Class ManageWsController
 * @package AppWebSocket\Controller\Server
 * @author Mykhailo YATSYHSYN <myyat@mirko.in.ua>
 * @copyright Mirko 1019-2020 <https://mirko.in.ua>
 */
class ManageWsController extends WsController
{

    public function access(): array
    {
        return [];
    }

    /**
     * Stop WebSocket server
     *
     * @param array|null $data
     */
    public function stopAction(?array $data = [])
    {
        $this->container->get(IoServer::class)->loop->stop();
    }
}