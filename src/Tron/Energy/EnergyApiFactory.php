<?php

namespace William\HyperfExtTron\Tron\Energy;


use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Tron\Energy\Apis\ApiInterface;
use William\HyperfExtTron\Tron\Energy\Attributes\EnergyApi;
use Hyperf\Context\ApplicationContext;
use Hyperf\Di\Annotation\AnnotationCollector;
use function Hyperf\Support\make;

class EnergyApiFactory
{
    protected array $instances = [];
    protected array $configs = [];

    public function __construct()
    {
        $configs = \Hyperf\Config\config('tron.apis');
        $this->configs = $configs;
//        $classes = AnnotationCollector::getClassesByAnnotation(EnergyApi::class);
//        foreach ($classes as $class => $annotation) {
//            /** @var EnergyApi $annotation */
//            $name = $annotation->name;
//            Logger::debug("Found EnergyApi: {$name} @ {$class}");
//            $instance = ApplicationContext::getContainer()->get($class);
//            $instance->init($configs[$name]);
//            $this->instances[$name] = $instance;
//        }
    }

    public function get(string $name): ApiInterface
    {
//        Logger::debug("Getting EnergyApi: {$name}");
//        return $this->instances[$name] ?? null;
        /** @var ApiInterface $instance */
        $instance = make($name);
        $instance->init($this->configs[$instance->name()]);
        return $instance;
    }
}
