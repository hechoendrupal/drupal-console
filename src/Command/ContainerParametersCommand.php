<?php

/**
 * @file
 * Contains \Drupal\Console\Command\ContainerParametersCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ContainerParametersCommand
 *
 * @package Drupal\Console\Command
 */
class ContainerParametersCommand extends Command {
  use ContainerAwareCommandTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('container:parameters')
      ->setDescription($this->trans('commands.container.parameters.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);

    $parametersList = $this->getParametersList();
    ksort($parametersList);
    $parameters = ['parameters' => $parametersList];
    $io->write(Yaml::dump($parameters, 4, 2));
    return 0;
  }

  private function getParametersList() {
    $parameters = array_filter($this->container->getParameterBag()->all(), function ($name) {
      if (preg_match('/^container\./', $name)) {
        return FALSE;
      }
      if (preg_match('/^drupal\./', $name)) {
        return FALSE;
      }
      if (preg_match('/^console\./', $name)) {
        return FALSE;
      }
      return TRUE;
    }, ARRAY_FILTER_USE_KEY);

    return $parameters;
  }

}
