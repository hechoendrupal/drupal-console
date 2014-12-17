<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\CacheRebuildCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheRebuildCommand extends ContainerAwareCommand
{

  protected function configure()
  {
    $this
      ->setName('cache:rebuild')
      ->setDescription('Rebuild and clear all site caches.')
      ->setAliases(['cr'])
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    require_once DRUPAL_ROOT . '/core/includes/utility.inc';

    $output->writeln('[+] <comment>Rebuilding site cache, wait a moment please</comment>');

    $kernelHelper = $this->getHelper('kernel');
    $classLoader = $kernelHelper->getClassLoader();
    $request = $kernelHelper->getRequest();

    \drupal_rebuild($classLoader, $request);

    $output->writeln('[+] <info>Done.</info>');
  }
}
