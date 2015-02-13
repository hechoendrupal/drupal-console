<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\SiteModeCommand.
 */
namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SiteModeCommand extends ContainerAwareCommand
{

  protected function configure()
  {
      $this
      ->setName('site:mode')
      ->setDescription($this->trans('commands.site.mode.description'))
      ->addArgument('environment', InputArgument::REQUIRED, $this->trans('commands.site.mode.arguments.environment'))
    ;
  }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $environment = $input->getArgument('environment');
        $config = $this->getConfigFactory()->getEditable('system.performance');

        if ('dev' === $environment) {
            $config->set('cache.page.use_internal', false);
            $config->set('css.preprocess', false);
            $config->set('css.gzip', false);
            $config->set('js.preprocess', false);
            $config->set('js.gzip', false);
            $config->set('response.gzip', false);
        }
        if ('prod' === $environment) {
            $config->set('cache.page.use_internal', true);
            $config->set('css.preprocess', true);
            $config->set('css.gzip', true);
            $config->set('js.preprocess', true);
            $config->set('js.gzip', true);
            $config->set('response.gzip', true);
        }

        $config->save();
    }
}
