<?php

/**
 * @file
 * Contains Drupal\Console\Command\ChainRegister.
 *
 * ChainRegister is a wrapper for Chain commands.
 * It will register the classes so you don't have to specify --file when calling
 * chain commands. i.e. drupal chain --file=/some-folder/chain-magic.yml will be
 * called: drupal chain:magic.
 *
 * To register custom chains, edit the ~/.console/chain.yml and add:
 * chain:
 *   name:
 *     'site:new:example':
 *        file: '/path-to-folder/chain-site-new.yml'
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
