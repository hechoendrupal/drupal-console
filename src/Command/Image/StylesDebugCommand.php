<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Image\StylesDebugCommand.
 */

namespace Drupal\Console\Command\Image;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class StylesDebugCommand
 *
 * @package Drupal\Console\Command\Image
 */
class StylesDebugCommand extends Command
{
    use CommandTrait;

    /**
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * StylesDebugCommand constructor.
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
            ->setName('image:styles:debug')
            ->setDescription($this->trans('commands.image.styles.debug.description'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $imageStyle = $this->entityTypeManager->getStorage('image_style');

        $io->newLine();
        $io->comment(
            $this->trans('commands.image.styles.debug.messages.styles-list')
        );

        if ($imageStyle) {
            $this->imageStyleList($io, $imageStyle);
        }

        return 0;
    }

    /**
     * @param \Drupal\Console\Core\Style\DrupalStyle $io
     * @param $imageStyle
     */
    protected function imageStyleList(DrupalStyle $io, $imageStyle)
    {
        $tableHeader = [
          $this->trans('commands.image.styles.debug.messages.styles-name'),
          $this->trans('commands.image.styles.debug.messages.styles-label')
        ];

        $tableRows = [];

        foreach ($imageStyle->loadMultiple() as $styles) {
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
