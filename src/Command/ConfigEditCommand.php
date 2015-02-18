<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\ConfigEditCommand.
 */
namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Process\Process;

class ConfigEditCommand extends ContainerAwareCommand
{
  protected function configure()
  {
    $this
      ->setName('config:edit')
      ->setDescription($this->trans('commands.config.edit.description'))
      ->addArgument('config-name', InputArgument::REQUIRED, $this->trans('commands.config.edit.arguments.config-name'))
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {

    $process = new Process('vim');
    $process->setTty('true');
    $process->run();
    if (!$process->isSuccessful()) {
      throw new \RuntimeException($process->getErrorOutput());
    }
    echo $process->getOutput();
  }

  /**
   * @param $output         OutputInterface
   * @param $table          TableHelper
   * @param $config_name    String
   */
  private function getConfigurationByName($output, $table, $config_name)
  {
    $configStorage = $this->getConfigStorage();
    if ($configStorage->exists($config_name)) {
      $table->setHeaders([$config_name]);

      $configuration = $configStorage->read($config_name);
      $configurationEncoded = Yaml::encode($configuration);

      $table->addRow([$configurationEncoded]);
    }
    $table->render($output);
  }
}