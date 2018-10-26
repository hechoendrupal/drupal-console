<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Debug\ImageStylesCommand.
 */

namespace Drupal\Console\Command\Debug;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class StylesDebugCommand
 *
 * @package Drupal\Console\Command\Debug
 */
class ImageStylesCommand extends Command
{
    /**
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * ImageStylesCommand constructor.
     *
     * @param EntityTypeManagerInterface $entityTypeManager
     */
    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:image:styles')
            ->setDescription($this->trans('commands.debug.image.styles.description'))
            ->setAliases(['dis']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $imageStyle = $this->entityTypeManager->getStorage('image_style');

        $this->getIo()->newLine();
        $this->getIo()->comment(
            $this->trans('commands.debug.image.styles.messages.styles-list')
        );

        if ($imageStyle) {
            $this->imageStyleList($imageStyle);
        }

        return 0;
    }

    /**
     * @param $imageStyle
     */
    protected function imageStyleList($imageStyle)
    {
        $tableHeader = [
          $this->trans('commands.debug.image.styles.messages.styles-name'),
          $this->trans('commands.debug.image.styles.messages.styles-label')
        ];

        $tableRows = [];

        foreach ($imageStyle->loadMultiple() as $styles) {
            $tableRows[] = [
              $styles->get('name'),
              $styles->get('label')
            ];
        }

        $this->getIo()->table(
            $tableHeader,
            $tableRows
        );
    }
}
