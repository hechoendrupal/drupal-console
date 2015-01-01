<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\CacheRebuildCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Core\Cache\Cache;

class CacheRebuildDiscoveryCommand extends ContainerAwareCommand
{

  protected function configure()
  {
    $this
      ->setName('cache:rebuild:discovery')
      ->setDescription('Rebuild the discovery cache.')
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    #require_once DRUPAL_ROOT . '/core/includes/utility.inc';

    $output->writeln('[+] <comment>Rebuilding discovery cache, wait a moment please</comment>');

    foreach (Cache::getBins() as $name => $bin) {
      if ($name == 'discovery' ) {
        $bin->deleteAll();
      }    
    }

    $output->writeln('[+] <info>Done.</info>');
  }
}
