<?php

namespace William\HyperfExtTron\Tron\Energy\Strategy;

use William\HyperfExtTron\Helper\Logger;
use William\HyperfExtTron\Tron\Energy\Attributes\Strategy;
use Hyperf\Di\Annotation\AnnotationCollector;
use function Hyperf\Support\make;

class StrategyFactory
{
    protected array $instances = [];

    public function __construct()
    {
        $classes = AnnotationCollector::getClassesByAnnotation(Strategy::class);

        foreach ($classes as $class => $annotation) {
            /** @var Strategy $annotation */
            $name = $annotation->name;
            Logger::debug("Found EnergyStrategy: {$name} @ {$class}");
            /** @var StrategyInterface $instance */
            $instance = make($class);
            $this->instances[$name] = $instance;
        }
    }

    public function get(string $name):?StrategyInterface
    {
        Logger::debug("Getting EnergyStrategy: {$name}");
        return $this->instances[$name] ?? null;
    }
}
