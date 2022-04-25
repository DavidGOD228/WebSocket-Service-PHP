<?php

namespace AppWebSocket\Controller;

use AppWebSocket\Kernel\Response;
use AppWebSocket\Kernel\ResponseCode;
use AppWebSocket\Kernel\WsUser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use AppWebSocket\Response\DataTransformer\DataTransformerInterface;

/**
 * Class WsControllerInterface
 * @package AppWebSocket\Controller
 * @author Mykhailo YATSYHSYN <myyat@mirko.in.ua>
 * @copyright Mirko 1019-2020 <https://mirko.in.ua>
 */
interface WsControllerInterface
{
    /**
     * Return list permission for access to actions
     *
     * @return array
     */
    public function access(): array;
}