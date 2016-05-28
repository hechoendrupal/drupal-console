<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Image\StylesDebugCommand.
 */

namespace Drupal\Console\Command\Image;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class StylesDebugCommand
 * @package Drupal\Console\Command\Image
 */
class StylesDebugCommand extends Command
{
    use ContainerAwareCommandTrait;
    protected function configure()
    {
        $this
            ->setName('image:styles:debug')
            ->setDescription($this->trans('commands.image.styles.debug.description'));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $image_handler = $this->getDrupalService('entity_type.manager')->getStorage('image_style');

        $io->newLine();
        $io->comment(
            $this->trans('commands.image.styles.debug.messages.styles-list')
        );

        if ($image_handler) {
            $this->imageStyleList($io, $image_handler);
        }
    }

    /**
     * @param \Drupal\Console\Style\DrupalStyle $io
     * @param $image_handler
     */
    protected function imageStyleList(DrupalStyle $io, $image_handler)
    {
        $tableHeader = [
          $this->trans('commands.image.styles.debug.messages.styles-name'),
          $this->trans('commands.image.styles.debug.messages.styles-label')
        ];

        foreach ($image_handler->loadMultiple() as $styles) {
            $tableRows[] = [
              $styles->get('name'),
              $styles->get('label')
            ];
        }

        $io->table(
            $tableHeader,
            $tableRows
        );
    }
}
