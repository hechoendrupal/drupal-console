<?php

/**
 * @file
 * Contains Drupal\Console\Command\ModuleTrait.
 */

namespace Drupal\Console\Command;

use Drupal\Console\Style\DrupalStyle;

/**
 * Class MenuTrait
 * @package Drupal\Console\Command
 */
trait MenuTrait
{
  /**
   * @param \Drupal\Console\Style\DrupalStyle $io
   * @return string
   * @throws \Exception
   */
  public function menuQuestion(DrupalStyle $io) {

    if ($io->confirm(
      $this->trans('commands.generate.form.questions.link'),
      true
    )) {
      // now we need to ask them where to gen the form
      // get the route
      $menu_options = [
        'link' => TRUE,
      ];
      $parent = $io->ask(
        $parent = $this->trans('commands.generate.form.questions.menu_parent'),
        'system.admin_config_development'
      );
      $description = $io->ask(
        $description = $this->trans('commands.generate.form.questions.description'),
        'A description for the menu entry'
      );
      $menu_options['parent'] = $parent;
      $menu_options['description'] = $description;
      return $menu_options;
    }
  }
}
