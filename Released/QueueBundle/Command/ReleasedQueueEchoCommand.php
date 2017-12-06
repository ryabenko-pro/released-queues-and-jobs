<?php


namespace Released\QueueBundle\Command;

use Released\Common\Command\BaseSingleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReleasedQueueEchoCommand extends BaseSingleCommand
{

    protected $counter = 0;

    protected function configure()
    {
        $this->setName('released:queue:echo');
    }


    /** {@inheritdoc} */
    public function doExecute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln($this->counter++);
    }
}