<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\MenuTrait.
 */

namespace Drupal\Console\Command\Shared;

use Drupal\Console\Core\Style\DrupalStyle;
use Symfony\Component\Yaml\Parser;

/**
 * Class MenuTrait
 *
 * @package Drupal\Console\Command
 */
trait MenuTrait
{
    /**
     * @param \Drupal\Console\Core\Style\DrupalStyle $io
     * @param string                                 $className The form class name
     * @return string
     * @throws \Exception
     */
    public function menuQuestion(DrupalStyle $io, $className)
    {
        if ($io->confirm(
            $this->trans('commands.generate.form.questions.menu_link_gen'),
            true
        )
        ) {
            // now we need to ask them where to gen the form
            // get the route
            $menu_options = [
                'menu_link_gen' => true,
            ];
            $menu_link_title = $io->ask(
                $menu_link_title = $this->trans('commands.generate.form.questions.menu_link_title'),
                $className
            );
            $menuLinkFile = sprintf(
                '%s/core/modules/system/system.links.menu.yml',
                $this->appRoot
            );

            $parser = new Parser();
            $menuLinkContent = $parser->parse(file_get_contents($menuLinkFile));


            $menu_parent = $io->choiceNoList(
                $menu_parent = $this->trans('commands.generate.form.questions.menu_parent'),
                array_keys($menuLinkContent),
                'system.admin_config_system'
            );

            $menu_link_desc = $io->ask(
                $menu_link_desc = $this->trans('commands.generate.form.questions.menu_link_desc'),
                'A description for the menu entry'
            );
            $menu_options['menu_link_title'] = $menu_link_title;
            $menu_options['menu_parent'] = $menu_parent;
            $menu_options['menu_link_desc'] = $menu_link_desc;
            return $menu_options;
        }
    }
}
