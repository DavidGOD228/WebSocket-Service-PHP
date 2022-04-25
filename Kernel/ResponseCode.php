<?php

namespace AppWebSocket\Kernel;

/**
 * Class ResponseCode
 * @package AppWebSocket\Kernel
 * @author Mykhailo YATSYHSYN <myyat@mirko.in.ua>
 * @copyright Mirko 1019-2020 <https://mirko.in.ua>
 */
class ResponseCode
{
    const SUCCESS = 200;
    const NEW_MESSAGE = 250;
    const KERNEL_ERROR = 300;
    const REQUEST_ERROR = 350;
    const ACCESS_DENIED = 400;
    const APP_ERROR = 500;
}