<?php

namespace AppWebSocket\Kernel;

/**
 * Class Request
 * @package AppWebSocket\Kernel
 * @author Mykhailo YATSYHSYN <myyat@mirko.in.ua>
 * @copyright Mirko 1019-2020 <https://mirko.in.ua>
 */
class Request
{
    /**
     * @param $request
     * @return array
     * @throws WsException
     */
    public function getRequest($request)
    {
        if(is_string($request)) {
            $request = json_decode($request, true);
            $this->validate($request);
        }

        return $this->parseRequest($request);
    }

    /**
     * @param $request
     * @throws WsException
     */
    private function validate($request)
    {

        if(!is_array($request)) {
            throw new WsException("Request is not valid", ResponseCode::REQUEST_ERROR);
        }

        if(!isset($request['route'])) {
            throw new WsException("Request is not valid. Route is require", ResponseCode::REQUEST_ERROR);
        }

        if(!isset($request['action'])) {
            throw new WsException("Request is not valid. Action is require", ResponseCode::REQUEST_ERROR);
        }
    }

    /**
     * @param array $request
     *
     * @return array
     */
    private function parseRequest(array $request)
    {
        return  [
            'controller' => $request['route'],
            'method' => $request['action'],
            'data' => isset($request['data']) ? $request['data'] : NULL
        ];
    }
}