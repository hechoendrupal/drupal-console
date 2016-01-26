<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Develop\TranslationCleanupCommand.
 */

namespace Drupal\Console\Command\Develop;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;
use Drupal\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;

class TranslationCleanupCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('translation:cleanup')
            ->setDescription($this->trans('commands.translation.cleanup.description'))
            ->addArgument(
                'language',
                InputArgument::OPTIONAL,
                $this->trans('commands.translation.cleanup.arguments.language'),
                null
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $language = $input->getArgument('language');

        $application = $this->getApplication();
        $appRoot = $application->getDirectoryRoot();

        $languages = $application->getConfig()->get('application.languages');
        unset($languages['en']);

        if ($language && !isset($languages[$language])) {
            $io->error(
                sprintf(
                    $this->trans('commands.translation.cleanup.messages.invalid-language'),
                    $language
                )
            );
            return 1;
        }

        if ($language) {
            $languages = [$language => $languages[$language]];
        }

        $this->cleanupTranslations($io, $language, $languages, $appRoot);

        $io->success(
            $this->trans('commands.translation.cleanup.messages.success')
        );
    }

    protected function cleanupTranslations($io, $language = null, $languages, $appRoot)
    {
        $finder = new Finder();

        foreach ($languages as $langCode => $languageName) {
            foreach ($finder->files()->name('*.yml')->in($appRoot . 'config/translations/' . $langCode) as $file) {
                $filename = $file->getBasename('.yml');
                if (!file_exists($appRoot . 'config/translations/en/' . $filename . '.yml')) {
                    $io->info(
                        sprintf(
                            $this->trans('commands.translation.cleanup.messages.file-deleted'),
                            $filename,
                            $languageName
                        )
                    );
                    unlink($appRoot . 'config/translations/' . $langCode. '/' . $filename . '.yml');
                }
            }
        }
    }
}
