<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\CacheRebuildCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Core\Cache\Cache;

class CacheRebuildCommand extends ContainerAwareCommand
{

  protected $caches = [];

  protected function configure()
  {
    $this
      ->setName('cache:rebuild')
      ->setDescription($this->trans('command.cache.rebuild.description'))
      ->setAliases(['cr'])
      ->addOption('cache', null, InputOption::VALUE_NONE, $this->trans('command.cache.rebuild.options.cache'))
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    require_once DRUPAL_ROOT . '/core/includes/utility.inc';

    $output->writeln('[+] <comment>'.$this->trans('command.cache.rebuild.messages.rebuild').'</comment>');

    $kernelHelper = $this->getHelper('kernel');
    $classLoader = $kernelHelper->getClassLoader();
    $request = $kernelHelper->getRequest();

    $cache = $input->getOption('cache');

    if ($cache == 'all') {
      \drupal_rebuild($classLoader, $request);
    }
    else {
      $caches = $this->getCaches();
      $caches[$cache]->deleteAll();
    }

    $output->writeln('[+] <info>'.$this->trans('command.cache.rebuild.messages.completed').'</info>');
  }

  protected function interact(InputInterface $input, OutputInterface $output)
  {
    $dialog = $this->getDialogHelper();

    $caches = $this->getCaches();
    $cache_keys = array_keys($caches);
    $cache_keys[] = 'all';

    // --cache option
    $cache = $input->getOption('cache');
    if (!$cache) {
      $cache = $dialog->askAndValidate(
        $output,
        $dialog->getQuestion($this->trans('command.cache.rebuild.questions.cache'),'all'),
        function ($cache) use($cache_keys) {
          if (!in_array($cache, array_values($cache_keys))) {
            throw new \InvalidArgumentException(
              sprintf(
                $this->trans('command.cache.rebuild.messages.invalid_cache'),
                $cache
              )
            );
          }
          return $cache;
        },
        false,
        'all',
        $cache_keys
      );
    }

    $input->setOption('cache', $cache);
  }

  protected function getCaches()
  {
    if (empty($this->caches)) {
      foreach (Cache::getBins() as $name => $bin) {
        $this->caches[$name] = $bin;
      }
    }

    return $this->caches;
  }
}
