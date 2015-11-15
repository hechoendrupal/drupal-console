<?php

/**
 * @file
 * Contains \Drupal\Console\Command\RouterRebuildCommand.
 */

namespace Drupal\Console\Command\Router;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;

class RebuildCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('router:rebuild')
            ->setDescription($this->trans('commands.router.rebuild.description'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('[+] <comment>'.$this->trans('commands.router.rebuild.messages.rebuilding').'</comment>');
        $container = $this->getContainer();
        $router_builder = $container->get('router.builder');
        $router_builder->rebuild();
        $output->writeln('[+] <info>'.$this->trans('commands.router.rebuild.messages.completed').'</info>');
    }
}
