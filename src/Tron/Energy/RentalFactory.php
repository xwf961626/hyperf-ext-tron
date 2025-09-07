<?php

namespace William\HyperfExtTron\Tron\Energy;

use William\HyperfExtTronService\SettingService;
use William\HyperfExtTron\Tron\Energy\Rental\RentalInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use function Hyperf\Support\make;

class RentalFactory
{
    protected array $instances = [];

    public function __construct(protected SettingService $settingService)
    {
        $configs = json_decode($this->settingService->get('energy_rental'), true);

        $classes = AnnotationCollector::getClassesByAnnotation(\William\HyperfExtTron\Tron\Energy\Attributes\Rental::class);

        foreach ($classes as $class => $annotation) {
            /** @var \William\HyperfExtTron\Tron\Energy\Attributes\Rental $annotation */
            $name = $annotation->name;
            /** @var RentalInterface $instance */
            $instance = make($class); // Laravel 容器
            if (method_exists($instance, 'init')) {
                $instance->init($configs[$name] ?? []);
            }
            $this->instances[$name] = $instance;
        }
    }

    public function get(string $name): ?RentalInterface
    {
        return $this->instances[$name] ?? null;
    }
}
