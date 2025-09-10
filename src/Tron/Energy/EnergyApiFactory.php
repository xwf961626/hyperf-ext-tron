<?php

namespace William\HyperfExtTron\Tron\Energy;


use Hyperf\Cache\Cache;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Router\Router;
use Hyperf\HttpServer\Contract\ResponseInterface;
use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Model\Api;
use William\HyperfExtTron\Tron\Energy\Apis\ApiInterface;
use William\HyperfExtTron\Tron\Energy\Attributes\EnergyApi;
use Hyperf\Context\ApplicationContext;
use Hyperf\Di\Annotation\AnnotationCollector;
use William\HyperfExtTron\Tron\TronService;
use function Hyperf\Config\config;
use function Hyperf\Support\make;

class EnergyApiFactory
{
    protected array $instances = [];
    protected array $configs = [];
    private mixed $_classes = [];

    public function __construct(protected Cache $cache)
    {
        $configs = config('tron.apis');
        $this->configs = $configs;
        foreach ($configs as $config) {
            $class = $config['class'];
            $instance = $this->create($class);
            $this->instances[$instance->name()] = $instance;
            $this->_classes[$class] = $instance;
            Logger::debug("创建API实例：$class");
        }
    }

    public function create(string $class): ApiInterface
    {
        /** @var ApiInterface $instance */
        $instance = make($class);
        $config = $this->configs[$instance->name()];
        if (!$api = Api::where('name', $instance->name())->first()) {
            $api = Api::create([
                'name' => $instance->name(),
                'url' => $instance->getBaseUrl(),
                'api_key' => $instance->getApiKey(),
            ]);
            $this->cache->delete(TronService::API_LIST_CACHE_KEY);
        }
        $instance->setModel($api);
        $instance->init($config);
        return $instance;
    }

    public function handleApiCallback(string $name, RequestInterface $request, ResponseInterface $response): mixed
    {
        Logger::debug("API的callback处理：$name");
        if (!isset($this->instances[$name])) {
            Logger::debug("API实例不存在: $name");
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
            Logger::debug("API未设置回调地址: $name");
            return $response->json(['code' => 404]);
        }
    }

    public function get(string $class): ApiInterface
    {
        return $this->_classes[$class];
    }
}
