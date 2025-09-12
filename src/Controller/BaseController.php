<?php

namespace William\HyperfExtTron\Controller;

use Hyperf\Contract\ContainerInterface;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;

class BaseController
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var ContainerInterface
     */
    protected $container;


    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->request = $container->get(RequestInterface::class);
        $this->response = $container->get(ResponseInterface::class);
    }

    protected function error($message, $code = 500)
    {
        return $this->response
            ->withStatus($code) // 设置状态码
            ->withHeader('Content-Type', 'application/json')
            ->withBody(new SwooleStream(json_encode(['code' => $code, 'msg' => $message])));
    }

    protected function success($data = [])
    {
        return $this->response
            ->withStatus(200) // 设置状态码
            ->withHeader('Content-Type', 'application/json')
            ->withBody(new SwooleStream(json_encode(['code' => 200, 'data' => $data])));
    }
}