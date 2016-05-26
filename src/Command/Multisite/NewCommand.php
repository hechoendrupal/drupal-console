<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\Multisite\NewCommand.
 */

namespace Drupal\Console\Command\Multisite;

use Symfony\Component\Console\Input\InputOption;
use Drupal\Console\Helper\HelperTrait;
use Drupal\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class MultisiteNewCommand
 * @package Drupal\Console\Command\Multisite
 */
class NewCommand extends Command
{
  use HelperTrait;

  /**
   * @{@inheritdoc}
   */
  public function configure()
  {
      $this
          ->setName('multisite:new')
          ->setDescription($this->trans('commands.multisite.new.description'))
          ->setHelp($this->trans('commands.multisite.new.help'))
          ->addArgument(
              'sites-subdir',
              InputOption::VALUE_REQUIRED,
              $this->trans('commands.multisite.new.arguments.sites-subdir')
          )
          ->addOption(
              'site-uri',
              '',
              InputOption::VALUE_OPTIONAL,
              $this->trans('commands.multisite.new.options.site-uri')
          )
          ->addOption(
              'copy-install',
              '',
              InputOption::VALUE_NONE,
              $this->trans('commands.multisite.new.options.copy-install')
          );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
      $subdir = $input->getArgument('sites-subdir');
      if (empty($subdir)) {
          $output->error($this->trans('commands.multisite.new.errors.subdir-empty'));
          return;
      }

      $output = new DrupalStyle($input, $output);
      $fs = new Filesystem();
      $root = $this->getDrupalHelper()->getRoot();

      if ($fs->exists($root . '/sites/' . $subdir)) {
          $output->error($this->trans('commands.multisite.new.errors.already-exists'));
          return;
      }

      if (!$fs->exists($root . '/sites/default')) {
          $output->error($this->trans('commands.multisite.new.errors.default-missing'));
          return;
      }

      $fs->mkdir($root . '/sites/' . $subdir, 0755);

      if ($uri = $input->getOption('site-uri')) {
          try {
              $this->addToSitesFile($output, $subdir, $uri);
          } catch (\Exception $e) {
              $output->error($e->getMessage());
              return;
          }
      }

      if ($input->getOption('copy-install')) {
          $this->copyExistingInstall($output, $subdir);
          return;
      }

      $this->createFreshSite($output, $subdir);
  }

  /**
   * @param DrupalStyle $output
   * @param string $subdir
   * @param string $uri
   */
  protected function addToSitesFile(DrupalStyle $output, $subdir, $uri)
  {
      $fs = new Filesystem();
      $root = $this->getDrupalHelper()->getRoot();

      if ($fs->exists($root . '/sites/sites.php')) {
          $sites_file_contents = file_get_contents($root . '/sites/sites.php');
      }
      elseif ($fs->exists($root . '/sites/example.sites.php')) {
          $sites_file_contents = file_get_contents($root . '/sites/example.sites.php');
          $sites_file_contents .= "\n\$sites = [];";
      }
      else {
          throw new \Exception($this->trans('commands.multisite.new.errors.missing-sites'));
      }

      $sites_file_contents .= "\n\$sites['$uri'] = '$subdir';";
      $fs->dumpFile($root . '/sites/sites.php', $sites_file_contents);
  }

  /**
   * @param DrupalStyle $output
   * @param string $subdir
   */
  protected function copyExistingInstall(DrupalStyle $output, $subdir)
  {
      $fs = new Filesystem();
      $root = $this->getDrupalHelper()->getRoot();

      if ($fs->exists($root . '/sites/default/files')) {
          $fs->mirror(
              $root . '/sites/default/files',
              $root . '/sites/' . $subdir . '/files'
          );
      }
      else {
          $output->warning('commands.multisite.new.warnings.missing-files');
      }

      $settings = file_get_contents($root . '/sites/default/settings.php');
      $settings = str_replace('sites/default', 'sites/' . $subdir, $settings);
      $fs->dumpFile(
          $root . '/sites/' . $subdir . '/settings.php',
          $settings
      );

      $output->success($this->trans('commands.multisite.new.messages.copy-install'));
  }

  /**
   * @param DrupalStyle $output
   * @param string $subdir
   */
  protected function createFreshSite(DrupalStyle $output, $subdir)
  {
      $fs = new Filesystem();
      $root = $this->getDrupalHelper()->getRoot();

      if ($fs->exists($root . '/sites/default/default.settings.php')) {
          $fs->copy(
              $root . '/sites/default/default.settings.php',
              $root . '/sites/' . $subdir . '/settings.php'
          );
      }
      else {
          $output->error('commands.multisite.new.errors.default-settings');
          return;
      }

      $output->success($this->trans('commands.multisite.new.messages.fresh-site'));
  }

}
