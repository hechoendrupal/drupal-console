<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\ConfigOverrideCommand.
 */
namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigOverrideCommand extends ContainerAwareCommand
{
  protected function configure()
  {
      $this
      ->setName('config:override')
      ->setDescription($this->trans('commands.config.override.description'))
      ->addArgument('config-name', InputArgument::REQUIRED, $this->trans('commands.config.override.arguments.config-name'))
      ->addArgument('key', InputArgument::REQUIRED, $this->trans('commands.config.override.arguments.key'))
      ->addArgument('value', InputArgument::REQUIRED, $this->trans('commands.config.override.arguments.value'));
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
      $table = $this->getHelperSet()->get('table');
      $configName = $input->getArgument('config-name');
      $key = $input->getArgument('key');
      $value = $input->getArgument('value');

      $config = $this->getConfigFactory()->getEditable($configName);

      $configurationOverrideResult = [];

      $configurationOverrideResult = $this->overrideConfiguration($config, $key, $value);

      $config->save();

      $output->writeln(sprintf(
        ' <info>%s:</info> <comment>%s</comment>',
        $this->trans('commands.config.override.messages.configuration-key'),
        $configName
      ));

      $table->setHeaders(
        [
          $this->trans('commands.config.override.messages.configuration'),
          $this->trans('commands.config.override.messages.original'),
          $this->trans('commands.config.override.messages.updated'),
        ]);
      $table->setlayout($table::LAYOUT_COMPACT);
      $table->setRows($configurationOverrideResult);
      $table->render($output);

      $config->save();
  }

  protected function overrideConfiguration($config, $key, $value)
  {
      $result[] = [
        'configuration' => $key,
        'original' => $config->get($key),
        'updated' => $value,
      ];
      $config->set($key, $value);

      return $result;
  }
}
