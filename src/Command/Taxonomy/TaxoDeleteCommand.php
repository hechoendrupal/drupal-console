<?php

namespace Drupal\Console\Command\Taxonomy;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class TaxoDeleteCommand.
 *
 * @package Drupal\eco_migrate
 */
class TaxoDeleteCommand extends BaseCommand {

  use ContainerAwareCommandTrait;

  use TermDeletionTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('taxo:delete')
      ->setDescription($this->trans('commands.taxo.delete.description'))
       ->addArgument('vid',InputArgument::REQUIRED);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $io = new DrupalStyle($input, $output);
    $this->deleteExistingTerms($input->getArgument('vid'),$io);
  }

}
