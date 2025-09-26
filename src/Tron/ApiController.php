<?php

namespace William\HyperfExtTron\Tron;

use Hyperf\Contract\ContainerInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use William\HyperfExtTron\Controller\BaseController;
use William\HyperfExtTron\Model\Api;
use William\HyperfExtTron\Model\UserResourceAddress;

/**
 * @\Hyperf\HttpServer\Annotation\Controller(prefix="admin")
 * Class AdminController
 * @package William\HyperfExtTron\Tron
 */
class ApiController extends BaseController
{
    #[Inject]
    protected TronService $service;



    public function addApiKey()
    {
        try {
            $result = $this->service->addTronApiKey($this->request);
            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function getTronApiKeyList()
    {
        try {
            $result = $this->service->getTronApiKeyList($this->request);
            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function getApiList()
    {
        try {
            $results = Api::orderBy('weight', 'desc')->get()->toArray();
            return $this->success($results);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function editApi()
    {
        try {
            $result = $this->service->editApi($this->request);
            $this->service->deleteApiCache();
            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}