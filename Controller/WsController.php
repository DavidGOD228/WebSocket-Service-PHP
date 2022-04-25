<?php

namespace AppWebSocket\Controller;

use AppWebSocket\Kernel\Response;
use AppWebSocket\Kernel\ResponseCode;
use AppWebSocket\Kernel\WsUser;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Domain\Chat\Model\ChatMember;
use Infrastructure\Chat\Doctrine\Repository\ChatMemberRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use AppWebSocket\Response\DataTransformer\DataTransformerInterface;

/**
 * Class WsController
 * @package AppWebSocket\Controller
 * @author Mykhailo YATSYHSYN <myyat@mirko.in.ua>
 * @copyright Mirko 1019-2020 <https://mirko.in.ua>
 */
abstract class WsController implements WsControllerInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Registry
     */
    protected $doctrine;

    /**
     * @var WsUser
     */
    private $wsUser;

    /**
     * @return WsUser
     */
    protected function getWsUser(): WsUser
    {
        return $this->wsUser;
    }

    /**
     * @param ContainerInterface $container
     */
    public function initializeContainer(ContainerInterface $container)
    {
        $this->container = $container;
        $this->doctrine = $container->get("doctrine");
    }

    /**
     * @param WsUser $wsUser
     */
    public function initializeWsUser(WsUser $wsUser)
    {
        $this->wsUser = $wsUser;
    }

    /**
     * @param array $entities
     */
    protected function clearDoctrineCache(array $entities)
    {
        /** @var ChatMemberRepository $chatMemberRepository */
        $chatMemberRepository = $this->doctrine->getRepository(ChatMember::class);

        foreach ($entities as $entity) {
            $chatMemberRepository->clearEntity($entity);
        }
    }

    /**
     * @param $dataTransformer
     * @param $object
     *
     * @param int $responseCode
     * @return Response
     */
    protected function response(
         $dataTransformer,
        $object,
        $responseCode = ResponseCode::SUCCESS
    )
    {
        return Response::create(
                $responseCode,
                $dataTransformer->getId($object),
                $dataTransformer->getAttributes($object)
        );
    }

    /**
     * @param $dataTransformer
     * @param array $collection
     * @param int $responseCode
     * @return Response
     */
    protected function responseCollection(
        $dataTransformer,
        $collection,
        $responseCode = ResponseCode::SUCCESS
    )
    {
        return Response::create(
            $responseCode,
            "collection",
            Response::execCollectionDataTransformer($dataTransformer, $collection)
        );
    }
}