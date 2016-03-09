<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Develop\TranslationSyncCommand.
 */

namespace Drupal\Console\Command\Develop;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Drupal\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;

class TranslationSyncCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('translation:sync')
            ->setDescription($this->trans('commands.translation.sync.description'))
            ->addArgument(
                'language',
                InputArgument::OPTIONAL,
                $this->trans('commands.translation.sync.arguments.language'),
                null
            )
            ->addOption(
                'file',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.translation.stats.options.file'),
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
        $file = $input->getOption('file');

        $application = $this->getApplication();
        $appRoot = $application->getDirectoryRoot();

        $languages = $application->getConfig()->get('application.languages');
        unset($languages['en']);

        if ($language && !isset($languages[$language])) {
            $io->error(
                sprintf(
                    $this->trans('commands.translation.stats.messages.invalid-language'),
                    $language
                )
            );
            return 1;
        }

        if ($language) {
            $languages = [$language => $languages[$language]];
        }

        $this->syncTranslations($io, $language, $languages, $file, $appRoot);

        $io->success($this->trans('commands.translation.sync.messages.sync-finished'));
    }

    protected function syncTranslations($io, $language = null, $languages, $file, $appRoot)
    {
        $englishFilesFinder = new Finder();
        $yaml = new Parser();
        $dumper = new Dumper();

        $englishDirectory = $appRoot . 'config/translations/en';

        if ($file) {
            $englishFiles = $englishFilesFinder->files()->name($file)->in($englishDirectory);
        } else {
            $englishFiles = $englishFilesFinder->files()->name('*.yml')->in($englishDirectory);
        }

        foreach ($englishFiles as $file) {
            $resource = $englishDirectory . '/' . $file->getBasename();
            $filename = $file->getBasename('.yml');

            try {
                $englishFile = file_get_contents($resource);
                $englishFileParsed = $yaml->parse($englishFile);
            } catch (ParseException $e) {
                $io->error($filename . '.yml: ' . $e->getMessage());
                continue;
            }

            foreach ($languages as $langCode => $languageName) {
                $languageDir = $appRoot . 'config/translations/' . $langCode;
                if (isset($language) && $langCode != $language) {
                    continue;
                }
                if (!isset($statistics[$langCode])) {
                    $statistics[$langCode] = ['total' => 0, 'equal'=> 0 , 'diff' => 0];
                }

                $resourceTranslated = $languageDir . '/' . $file->getBasename();
                if (!file_exists($resourceTranslated)) {
                    file_put_contents($resourceTranslated, $englishFile);
                    $io->info(
                        sprintf(
                            $this->trans('commands.translation.sync.messages.created-file'),
                            $file->getBasename(),
                            $languageName
                        )
                    );
                    continue;
                }

                try {
                    //print $resourceTranslated . "\n";
                    $resourceTranslatedParsed = $yaml->parse(file_get_contents($resourceTranslated));
                } catch (ParseException $e) {
                    $io->error($resourceTranslated . ':' . $e->getMessage());
                    continue;
                }

                $resourceTranslatedParsed = array_replace_recursive($englishFileParsed, $resourceTranslatedParsed);

                try {
                    $resourceTranslatedParsedYaml = $dumper->dump($resourceTranslatedParsed, 10);
                } catch (\Exception $e) {
                    $io->error(
                        sprintf(
                            $this->trans('commands.translation.sync.messages.error-generating'),
                            $resourceTranslated,
                            $languageName,
                            $e->getMessage()
                        )
                    );

                    continue;
                }

                try {
                    file_put_contents($resourceTranslated, $resourceTranslatedParsedYaml);
                } catch (\Exception $e) {
                    $io->error(
                        sprintf(
                            '%s: %s',
                            $this->trans('commands.translation.sync.messages.error-writing'),
                            $resourceTranslated,
                            $languageName,
                            $e->getMessage()
                        )
                    );

                    return 1;
                }
            }
        }
    }
}
