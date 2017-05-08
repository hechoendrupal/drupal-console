<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Behat\RunCommand.
 */

namespace Drupal\Console\Command\Behat;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;

class RunCommand extends Command
{
    use ContainerAwareCommandTrait;
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
          ->setName('behat:run')
          ->setDescription($this->trans('commands.behat.run.description'))
          ->addArgument(
            'filter',
            InputArgument::OPTIONAL,
            $this->trans('commands.behat.run.arguments.filter')
          );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        try {
            $behatdir = $this->getDrupalContainer()->getParameter('behat.dir');
        } catch (ParameterNotFoundException $pe) {
            $io->error($this->trans('commands.behat.run.error'));
            return NULL;
        }
        $dir = $this->get('site')->getRoot() . '/' . $behatdir;

        $filter = $input->getArgument('filter');
        $this->get('chain_queue')->addCommand(
          'exec',
          ['bin' => "cd $dir && vendor/bin/behat $filter --colors"]
        );
    }

}
