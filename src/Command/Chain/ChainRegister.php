<?php

/**
 * @file
 * Contains Drupal\Console\Command\ChainRegister.
 */

namespace Drupal\Console\Command\Chain;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Console\Application;
use Drupal\Console\Utils\ConfigurationManager;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ChainFilesTrait;
use Drupal\Console\Style\DrupalStyle;
/**
 * Class ChainRegister
 *
 * @package Drupal\Console\Command\ChainRegister
 */
class ChainRegister extends ChainCommand {
  use ChainFilesTrait;

  /**
   * ChainRegister constructor.
   *
   * @param $name Chain name
   * @param $file File name
   */
  public function __construct($name, $file) {
    $this->setName($name);
    $this->setFile($file);

    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    parent::configure();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    parent::interact($input, $output);

    $io = new DrupalStyle($input, $output);

    // Populate placeholders.
    $placeholders = '';
    foreach ($input->getOption('placeholder') as $placeholder) {
      $placeholders .= sprintf('--placeholder="%s" ',
        $placeholder
      );
    }

    $command = sprintf('drupal chain --file %s %s',
      $this->file,
      $placeholders
    );

    // Run.
    $shellProcess = $this->get('shell_process');

    if (!$shellProcess->exec($command, TRUE)) {
      $io->error(
        sprintf(
          $this->trans('commands.exec.messages.invalid-bin')
        )
      );

      return 1;
    }
  }
}
