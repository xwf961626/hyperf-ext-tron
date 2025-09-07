<?php

namespace William\HyperfExtTron\Tron;

use Hyperf\Contract\ContainerInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;

/**
 * @\Hyperf\HttpServer\Annotation\Controller(prefix="admin")
 * Class AdminController
 * @package William\HyperfExtTron\Tron
 */
class AdminController
{
    #[Inject]
    protected TronService $service;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ResponseInterface
     */
    protected $response;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->request = $container->get(RequestInterface::class);
        $this->response = $container->get(ResponseInterface::class);
    }

    public function addApiKey()
    {
        try {
            $result = $this->service->addTronApiKey($this->request);
            return $this->response->json(['code' => 0, 'msg' => 'success', 'data' => $result]);
        } catch (\Exception $e) {
            return $this->response->json(['code' => 0, 'msg' => $e->getMessage()]);
        }
    }

    public function getTronApiKeyList()
    {
        try {
            $result = $this->service->getTronApiKeyList($this->request);
            return $this->response->json(['code' => 0, 'msg' => 'success', 'data' => $result]);
        } catch (\Exception $e) {
            return $this->response->json(['code' => 0, 'msg' => $e->getMessage()]);
        }
    }
}