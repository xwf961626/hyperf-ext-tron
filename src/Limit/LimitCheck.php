<?php

namespace William\HyperfExtTron\Limit;

class LimitCheck
{
    protected LimitHandlerInterface $callback;
    protected int $interval = 10;
    protected RuleInterface $rule;
    protected string $name;

    public function __construct(protected mixed $model)
    {
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setCallback(LimitHandlerInterface $handler): LimitCheck
    {
        $this->callback = $handler;
        return $this;
    }

    public function setInterval(int $seconds): LimitCheck
    {
        $this->interval = $seconds;
        return $this;
    }

    public function setRule(RuleInterface $rule): LimitCheck
    {
        $this->rule = $rule;
        return $this;
    }

    public function getInterval(): int
    {
        return $this->interval;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function getRule():RuleInterface
    {
        return $this->rule;
    }

    public function getCallback():LimitHandlerInterface
    {
        return $this->callback;
    }
}