<?php

/**
 * @file
 * Contains \Drupal\Console\Command\RouterRebuildCommand.
 */

namespace Drupal\Console\Command\Router;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;

class RebuildCommand extends Command
{
    use ContainerAwareCommandTrait;
    protected function configure()
    {
        $this
            ->setName('router:rebuild')
            ->setDescription($this->trans('commands.router.rebuild.description'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $io->newLine();
        $io->comment(
            $this->trans('commands.router.rebuild.messages.rebuilding')
        );

        $router_builder = $this->getService('router.builder');
        $router_builder->rebuild();

        $io->success(
            $this->trans('commands.router.rebuild.messages.completed')
        );
    }
}
