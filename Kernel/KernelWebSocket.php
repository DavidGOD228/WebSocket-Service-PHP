<?php

namespace AppWebSocket\Kernel;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Application\SharedKernel\Service\ContainerService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class KernelWebSocket
 * @package AppWebSocket\Kernel
 * @author Mykhailo YATSYHSYN <myyat@mirko.in.ua>
 * @copyright Mirko 1019-2020 <https://mirko.in.ua>
 */
class KernelWebSocket implements MessageComponentInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var \Doctrine\ORM\EntityManager|object|null
     */
    private $em;

    /**
     * EngineWebSocket constructor.
     * @param ContainerInterface $container\
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->userManager = new UserManager();
        $this->container->set(UserManager::class, $this->userManager);
        $this->em = $this->container->get("doctrine.orm.entity_manager");
        ContainerService::setContainer($container);
    }

    /**
     * @inheritDoc
     */
    function onOpen(ConnectionInterface $conn)
    {
        $this->dbConnect();

        $this->userManager->addConnection($conn);
    }

    /**
     * @inheritDoc
     */
    function onClose(ConnectionInterface $conn)
    {
        $this->dbConnect();

        $this->userManager->closeConnection($conn);
    }

    /**
     * @inheritDoc
     */
    function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->dbConnect();

        $response = Response::createFromException($e);
        $conn->send($response->prepareForSend());
        unset($response);
    }

    /**
     * @inheritDoc
     */
    function onMessage(ConnectionInterface $from, $msg)
    {
        $this->dbConnect();

        try {
            $requestParser = new Request();
            $request = $requestParser->getRequest($msg);
            unset($requestParser);
            $wsUser = $this->userManager->getByConnection($from);
            $action = new Action($this->container);
            $response = $action->execute($request, $wsUser);
            unset($action);
        } catch (WsException | \Exception $exception) {
            $response = Response::createFromException($exception);
        }

        if($response instanceof Response) {
            $from->send($response->prepareForSend());
        }

        unset($response);

        $links = gc_collect_cycles();
        echo "\n\r link remove:". $links;
    }

    /**
     * Reconnect to database
     */
    private function dbConnect()
    {
        if(!$this->em->getConnection()->ping()) {
            if($this->em->getConnection()->isConnected()) {
                $this->em->getConnection()->close();
            }

            $this->em->getConnection()->connect();
            echo "\n\r Reconnect to DB ".date("Y-m-d H:i:s")." \n\r";
        }
    }
}