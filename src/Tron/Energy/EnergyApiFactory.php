<?php

namespace William\HyperfExtTron\Tron\Energy;


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
        $instance->init($this->configs[$instance->name()]);
        $api = Api::updateOrCreate([
            'name' => $instance->name(),
            'api_key' => $instance->getApiKey(),
            'url' => $instance->getBaseUrl(),
        ]);
        $instance->setModel($api);
        return $instance;
    }

    public function get(string $name): ApiInterface
    {
        return $this->instances[$name];
    }
}
