<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\RouterRebuildCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RouterRebuildCommand extends ContainerAwareCommand
{

  protected function configure()
  {
    $this
      ->setName('router:rebuild')
      ->setDescription('Displays current routes for an application')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $output->writeln('[+] <comment>Rebuilding routes, wait a moment please</comment>');
    $container = $this->getContainer();
    $router_builder = $container->get('router.builder');
    $router_builder->rebuild();
    $output->writeln('[+] <info>Done.</info>');
  }
}
