<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Image\StylesFlushCommand.
 */
namespace Drupal\Console\Command\Image;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Console\Core\Style\DrupalStyle;

class StylesFlushCommand extends Command
{
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

    protected function configure()
    {
        $this
            ->setName('image:styles:flush')
            ->setDescription($this->trans('commands.image.styles.flush.description'))
            ->addArgument(
                'styles',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                $this->trans('commands.image.styles.flush.options.image-style')
            )->setAliases(['isf']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $styles = $input->getArgument('styles');
        if (!$styles) {
            $imageStyle = $this->entityTypeManager->getStorage('image_style');
            $styleList = $imageStyle->loadMultiple();
            $styleNames = [];
            foreach ($styleList as $style) {
                $styleNames[] = $style->get('name');
            }

            $styles = $io->choice(
                $this->trans('commands.image.styles.flush.questions.image-style'),
                $styleNames,
                null,
                true
            );

            $input->setArgument('styles', $styles);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $styles = $input->getArgument('styles');
        $result = 0;

        $imageStyle = $this->entityTypeManager->getStorage('image_style');
        $stylesNames = [];
        if (in_array('all', $styles)) {
            $styles = $imageStyle->loadMultiple();
            foreach ($styles as $style) {
                $stylesNames[] = $style->get('name');
            }

            $styles = $stylesNames;
        }

        foreach ($styles as $style) {
            try {
                $io->info(
                    sprintf(
                        $this->trans('commands.image.styles.flush.messages.executing-flush'),
                        $style
                    )
                );
                $imageStyle->load($style)->flush();
            } catch (\Exception $e) {
                watchdog_exception('image', $e);
                $io->error($e->getMessage());
                $result = 1;
            }
        }

        $io->success($this->trans('commands.image.styles.flush.messages.success'));

        return $result;
    }
}
