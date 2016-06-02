<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Image\StylesFlushCommand.
 */
namespace Drupal\Console\Command\Image;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;

class StylesFlushCommand extends Command
{
    use ContainerAwareCommandTrait;
    protected function configure()
    {
        $this
            ->setName('image:styles:flush')
            ->setDescription($this->trans('commands.image.styles.flush.description'))
            ->addArgument(
                'styles',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                $this->trans('commands.image.styles.flush.options.image-style')
            );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $styles = $input->getArgument('styles');
        if (!$styles) {
            $image_handler = $this->getDrupalService('entity_type.manager')->getStorage('image_style');
            $styleList = $image_handler->loadMultiple();
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

        $image_handler = $this->getDrupalService('entity_type.manager')->getStorage('image_style');

        if (in_array('all', $styles)) {
            $styles = $image_handler->loadMultiple();

            foreach ($styles as $style) {
                $styles_names[] = $style->get('name');
            }

            $styles = $styles_names;
        }

        foreach ($styles as $style) {
            try {
                $io->info(
                    sprintf(
                        $this->trans('commands.image.styles.flush.messages.executing-flush'),
                        $style
                    )
                );

                $image_handler->load($style)->flush();
            } catch (\Exception $e) {
                watchdog_exception('image', $e);
                $io->error($e->getMessage());
            }
        }

        $io->success($this->trans('commands.image.styles.flush.messages.success'));
    }
}
