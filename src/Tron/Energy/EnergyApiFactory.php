<?php

namespace William\HyperfExtTron\Tron\Energy;


use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Router\Router;
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

    public function registerCallbackRoutes(): void
    {
        Logger::debug("注册API的callback url路由");
        /**
         * @var  $name
         * @var ApiInterface $instance
         */
        foreach ($this->instances as $name => $instance) {
            if ($route = $instance->callbackUrl()) {
                // 如果是完整 URL，取 path；如果本来就是 path，就直接用
                $path = parse_url($route, PHP_URL_PATH) ?: $route;

                // 确保以 / 开头，避免漏掉
                if ($path[0] !== '/') {
                    $path = '/' . $path;
                }
                Logger::debug("注册API#{$name}回调地址：$route => $path");
                Router::post($path, function (RequestInterface $request) use ($instance) {
                    return $instance->getCallbackHandler($request);
                });
            }
        }
    }

    public function get(string $name): ApiInterface
    {
        return $this->instances[$name];
    }
}
