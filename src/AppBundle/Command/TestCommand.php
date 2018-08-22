<?php

namespace AppBundle\Command;

use AppBundle\Bot\TelegramBot;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends  Command
{
    private $bot;

    public function __construct(TelegramBot $bot)
    {
        $this->bot = $bot;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("bot:start");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->bot->test();
    }


}