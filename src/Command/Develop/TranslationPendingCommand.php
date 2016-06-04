<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Develop\TranslationPendingCommand.
 */

namespace Drupal\Console\Command\Develop;

use Drupal\Console\Command\Shared\TranslationTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser;
use Drupal\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;

class TranslationPendingCommand extends Command
{
    use TranslationTrait;
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('translation:pending')
            ->setDescription($this->trans('commands.translation.pending.description'))
            ->addArgument(
                'language',
                InputArgument::REQUIRED,
                $this->trans('commands.translation.pending.arguments.language'),
                null
            )
            ->addOption(
                'file',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.translation.pending.options.file'),
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
                    $this->trans('commands.translation.pending.messages.invalid-language'),
                    $language
                )
            );
            return 1;
        }

        if ($language) {
            $languages = [$language => $languages[$language]];
        }

        $pendingTranslations = $this->determinePendingTranslation($io, $language, $languages, $file, $appRoot);

        if ($file) {
            $io->success(
                sprintf(
                    $this->trans('commands.translation.pending.messages.success-language-file'),
                    $pendingTranslations,
                    $languages[$language],
                    $file
                )
            );
        } else {
            $io->success(
                sprintf(
                    $this->trans('commands.translation.pending.messages.success-language'),
                    $pendingTranslations,
                    $languages[$language]
                )
            );
        }
    }

    protected function determinePendingTranslation($io, $language = null, $languages, $fileFilter, $appRoot)
    {
        $nestedArray = $this->getNestedArrayHelper();
        $englishFilesFinder = new Finder();
        $yaml = new Parser();
        $statistics = [];

        $englishDirectory = $appRoot . 'config/translations/en';

        $englishFiles = $englishFilesFinder->files()->name('*.yml')->in($englishDirectory);

        $pendingTranslations = 0;
        foreach ($englishFiles as $file) {
            $resource = $englishDirectory . '/' . $file->getBasename();
            $filename = $file->getBasename('.yml');

            if ($fileFilter && $fileFilter != $file->getBasename()) {
                continue;
            }

            try {
                $englishFileParsed = $yaml->parse(file_get_contents($resource));
            } catch (ParseException $e) {
                $io->error($filename . '.yml: ' . $e->getMessage());
                continue;
            }

            foreach ($languages as $langCode => $languageName) {
                $languageDir = $appRoot . 'config/translations/' . $langCode;
                if (isset($language) && $langCode != $language) {
                    continue;
                }

                $resourceTranslated = $languageDir . '/' . $file->getBasename();
                if (!file_exists($resourceTranslated)) {
                    $io->info(
                        sprintf(
                            $this->trans('commands.translation.pending.messages.missing-file'),
                            $languageName,
                            $file->getBasename()
                        )
                    );
                    continue;
                }

                try {
                    $resourceTranslatedParsed = $yaml->parse(file_get_contents($resourceTranslated));
                } catch (ParseException $e) {
                    $io->error($resourceTranslated . ':' . $e->getMessage());
                }

                $diffStatistics = ['total' => 0, 'equal' => 0, 'diff' => 0];
                $diff = $nestedArray->arrayDiff($englishFileParsed, $resourceTranslatedParsed, true, $diffStatistics);

                if (!empty($diff)) {
                    $diffFlatten = array();
                    $keyFlatten = '';
                    $nestedArray->yamlFlattenArray($diff, $diffFlatten, $keyFlatten);

                    $tableHeader = [
                        $this->trans('commands.yaml.diff.messages.key'),
                        $this->trans('commands.yaml.diff.messages.value'),
                    ];

                    $tableRows = [];
                    foreach ($diffFlatten as $yamlKey => $yamlValue) {
                        if ($this->isYamlKey($yamlValue)) {
                            unset($diffFlatten[$yamlKey]);
                        } else {
                            $tableRows[] = [
                                $yamlKey,
                                $yamlValue
                            ];
                        }
                    }

                    if (count($diffFlatten)) {
                        $io->writeln(
                            sprintf(
                                $this->trans('commands.translation.pending.messages.pending-translations'),
                                $languageName,
                                $file->getBasename()
                            )
                        );

                        $io->table($tableHeader, $tableRows, 'compact');
                        $pendingTranslations+= count($diffFlatten);
                    }
                }
            }
        }

        return $pendingTranslations;
    }
}
