<?php

namespace AppWebSocket\Response\DataTransformer;

/**
 * Interface DataTransformerInterface
 * @package AppWebSocket\DataTransformer
 * @author Mykhailo YATSYHSYN <myyat@mirko.in.ua>
 * @copyright Mirko 1019-2020 <https://mirko.in.ua>
 */
interface DataTransformerInterface
{
    /**
     * @param $object
     * @return int|string|null
     */
    public function getId($object);

    /**
     * @param $object
     * @return array
     */
    public function getAttributes($object): array;
}