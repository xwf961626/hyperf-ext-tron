<?php

namespace William\HyperfExtTron\Tron\Energy;


use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Router\Router;
use Hyperf\HttpServer\Contract\ResponseInterface;
use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Model\Api;
use William\HyperfExtTron\Tron\Energy\Apis\ApiInterface;
use William\HyperfExtTron\Tron\Energy\Attributes\EnergyApi;
use Hyperf\Context\ApplicationContext;
use Hyperf\Di\Annotation\AnnotationCollector;
use function Hyperf\Config\config;
use function Hyperf\Support\make;

class EnergyApiFactory
{
    protected array $instances = [];
    protected array $configs = [];

    public function __construct()
    {
        $configs = config('tron.apis');
        $this->configs = $configs;
        foreach ($configs as $config) {
            $class = $config['class'];
            $this->instances[$class] = $this->create($class);
        }
    }

    public function create(string $class): ApiInterface
    {
        /** @var ApiInterface $instance */
        $instance = make($class);
        $config = $this->configs[$instance->name()];
        $api = Api::updateOrCreate([
            'name' => $instance->name(),
        ], [
            'url' => $instance->getBaseUrl(),
            'api_key' => $instance->getApiKey(),
        ]);
        $instance->setModel($api);
        $instance->init($config);
        return $instance;
    }

    public function handleApiCallback(string $name, RequestInterface $request, ResponseInterface $response): mixed
    {
        Logger::debug("API的callback处理：$name");
        if (!isset($this->instances[$name])) {
            return $response->json(['code' => 404]);
        }
        /**
         * @var  $name
         * @var ApiInterface $instance
         */
        $instance = $this->instances[$name];
        if ($route = $instance->callbackUrl()) {
            // 如果是完整 URL，取 path；如果本来就是 path，就直接用
            $path = parse_url($route, PHP_URL_PATH) ?: $route;

            // 确保以 / 开头，避免漏掉
            if ($path[0] !== '/') {
                $path = '/' . $path;
            }
            Logger::debug("注册API#{$name}回调地址：$route => $path");
            if ($handler = $instance->getCallbackHandler($request))
                return $handler($request);
            else
                return $response->json(['code' => 405]);
        } else {
            return $response->json(['code' => 404]);
        }
    }

    public function get(string $name): ApiInterface
    {
        return $this->instances[$name];
    }
}
