<?php

namespace AppWebSocket\Kernel;

use AppWebSocket\Controller\WsController;
use Domain\Guard\Exception\GuardException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Action
 * @package AppWebSocket\Kernel
 * @author Mykhailo YATSYHSYN <myyat@mirko.in.ua>
 * @copyright Mirko 1019-2020 <https://mirko.in.ua>
 */
class Action
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Action constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param array $request
     *
     * @param WsUser $wsUser
     * @return mixed
     *
     * @throws WsException
     */
    public function execute(array $request, WsUser $wsUser)
    {
        $controllerClass = "\\AppWebSocket\\Controller\\".$request['controller']."WsController";
        if(!class_exists($controllerClass)) {
            throw new WsException(
                "Bad request. Controller {$controllerClass} not exist",
                ResponseCode::REQUEST_ERROR
            );
        }

        $controllerMethod = $request['method']."Action";
        if(!method_exists($controllerClass, $controllerMethod)) {
            throw new WsException(
                "Bad request. Action {$controllerMethod} in controller {$controllerClass} not exist",
                ResponseCode::REQUEST_ERROR
            );
        }

        /** @var WsController $controller */
        $controller = new $controllerClass;
        $controller->initializeContainer($this->container);
        $controller->initializeWsUser($wsUser);
        if(method_exists($controller, "construct")) {
            $controller->construct();
        }

        try {
            $response = $controller->$controllerMethod($request['data']);
        } catch (GuardException $exception) {
            throw new WsException($exception->getMessage(), ResponseCode::ACCESS_DENIED);
        }

        if($response instanceof Response) {
            $response->setRoute($request['controller']);
            $response->setAction($request['method']);
        }

        unset($controller);
        return $response;
    }
}