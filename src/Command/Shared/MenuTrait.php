<?php

/**
 * @file
 * Contains Drupal\Console\Command\Shared\MenuTrait.
 */

namespace Drupal\Console\Command\Shared;

use Symfony\Component\Yaml\Parser;

/**
 * Class MenuTrait
 *
 * @package Drupal\Console\Command
 */
trait MenuTrait
{
    /**
     * @param string                                 $className The form class name
     * @return string
     * @throws \Exception
     */
    public function menuQuestion($className)
    {
        if ($this->getIo()->confirm(
            $this->trans('commands.generate.form.options.menu-link-gen'),
            true
        )
        ) {
            // now we need to ask them where to gen the form
            // get the route
            $menu_options = [
                'menu_link_gen' => true,
            ];
            $menu_link_title = $this->getIo()->ask(
                $menu_link_title = $this->trans('commands.generate.form.options.menu-link-title'),
                $className
            );
            $menuLinkFile = sprintf(
                '%s/core/modules/system/system.links.menu.yml',
                $this->appRoot
            );

            $parser = new Parser();
            $menuLinkContent = $parser->parse(file_get_contents($menuLinkFile));


            $menu_parent = $this->getIo()->choiceNoList(
                $menu_parent = $this->trans('commands.generate.form.options.menu-parent'),
                array_keys($menuLinkContent),
                'system.admin_config_system'
            );

            $menu_link_desc = $this->getIo()->ask(
                $menu_link_desc = $this->trans('commands.generate.form.options.menu-link-desc'),
                $menu_link_desc = $this->trans('commands.generate.form.suggestions.description-for-menu')
            );
            $menu_options['menu_link_title'] = $menu_link_title;
            $menu_options['menu_parent'] = $menu_parent;
            $menu_options['menu_link_desc'] = $menu_link_desc;
            return $menu_options;
        }
    }
}
