<?php

namespace AppWebSocket\Kernel;

use AppWebSocket\Response\DataTransformer\DataTransformerInterface;

/**
 * Class Response
 * @package AppWebSocket\Kernel
 * @author Mykhailo YATSYHSYN <myyat@mirko.in.ua>
 * @copyright Mirko 1019-2020 <https://mirko.in.ua>
 */
class Response
{
    private $code;
    private $id;
    private $attributes;
    private $route;
    private $action;


    /**
     * Build response data for collection
     *
     * @param DataTransformerInterface $dataTransformer
     * @param array $collection
     *
     * @return array
     */
    public static function execCollectionDataTransformer(
        $dataTransformer,
        array $collection
    )
    {
        $results = [];
        foreach ($collection as $item) {
           $results[] = self::execDataTransformer($dataTransformer, $item);
        }

        unset($dataTransformer);
        return $results;
    }

    /**
     * Build response data
     *
     * @param  $dataTransformer
     * @param $object
     *
     * @return array
     */
    public static function execDataTransformer(
        $dataTransformer,
        $object
    ) {
        return [
            "id" => $dataTransformer->getId($object),
            "attributes" => $dataTransformer->getAttributes($object)
        ];
    }

    /**
     * @param int $code
     * @param $id
     * @param array $attributes
     *
     * @return Response
     */
    public static function create(int $code, $id, array $attributes)
    {
        $response = new Response();
        $response->id = $id;
        $response->code = $code;
        $response->attributes = $attributes;

        return $response;
    }

    /**
     * @param \Exception $exception
     * @return Response
     */
    public static function createFromException(\Exception $exception)
    {
        return self::create(
            $exception->getCode(),
            "N/A",
            [
                "message" => $exception->getMessage(),
                "file" => $exception->getFile(),
                "line" => $exception->getLine()
            ]
        );
    }

    /**
     * @return false|string
     */
    public function prepareForSend()
    {
        return json_encode([
            'route' => $this->route,
            'action' => $this->action,
            'code' => $this->code,
            'id' => $this->id,
            'attributes' => $this->attributes
        ]);
    }

    /**
     * @param mixed $action
     */
    public function setAction($action): void
    {
        $this->action = $action;
    }

    /**
     * @param mixed $route
     */
    public function setRoute($route): void
    {
        $this->route = $route;
    }
}