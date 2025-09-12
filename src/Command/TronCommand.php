<?php

namespace William\HyperfExtTron\Command;

use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Symfony\Component\Console\Input\InputArgument;
use William\HyperfExtTron\Tron\TronApiKey;

#[Command]
class TronCommand extends HyperfCommand
{
    public function __construct()
    {
        parent::__construct('tron:add_api_key');
    }

    public function configure()
    {
        $this->setDescription('Add an tron api key');
        $this->addArgument('type', InputArgument::REQUIRED, 'key type node or scan');
        $this->addArgument('keys', InputArgument::REQUIRED, 'api keys join by ,');
    }

    public function handle()
    {
        $type = $this->input->getArgument('type');
        $keys = $this->input->getArgument('keys');

        foreach (explode(',', $keys) as $apiKey) {
            if (TronApiKey::query()->where('api_key', $apiKey)->exists()) {
                $this->output->writeln("<error>Api key '{$apiKey}' already exists.</error>");
                continue;
            }
            TronApiKey::create(['api_key' => $apiKey, 'type' => $type]);
            $this->output->writeln("<error>Add Api key '{$apiKey}' successfully.</error>");
        }
        $this->output->writeln("<info>all successfully!</info>");
    }
}