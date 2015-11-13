<?php

/**
 * @file
 * Contains \Drupal\Console\Command\ElephantCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ElephantCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('elephant')
            ->setDescription('phhhh');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $renderer = $this->getRenderHelper();
        $application = $this->getApplication();
        $parameters = [
            'elephant'=> rand(0,3)
        ];

        $about = $renderer->render('core/elephant.twig', $parameters);

        $output->writeln($about);
    }
}
