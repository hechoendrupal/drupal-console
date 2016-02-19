<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Image\StylesDebugCommand.
 */

namespace Drupal\Console\Command\Image;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;


/**
 * Class StylesDebugCommand
 * @package Drupal\Console\Command\Image
 */
class StylesDebugCommand extends ContainerAwareCommand
{
    protected function configure()
    {
      $this
        ->setName('image:styles:debug')
        ->setDescription($this->trans('commands.image.styles.debug.description'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
      $io = new DrupalStyle($input, $output);
      $module_handler = $this->getModuleHandler();

      $io->section(
        $this->trans('commands.image.styles.debug.messages.styles-list')
      );

      $io->table(
        [$this->trans('commands.image.styles.debug.messages.styles')],
        $module_handler->getImplementations('image'),
        'compact'
      );
    }

}
