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
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Utils\ConfigurationManager;
use Drupal\Console\Core\Utils\NestedArray;

class TranslationPendingCommand extends Command
{
    use TranslationTrait;
    use CommandTrait;

    /**
     * @var string
     */
    protected $consoleRoot;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
      * @var NestedArray
      */
    protected $nestedArray;


    /**
     * TranslationPendingCommand constructor.
     *
     * @param $consoleRoot
     * @param $configurationManager
     * @param NestedArray          $nestedArray
     */
    public function __construct(
        $consoleRoot,
        ConfigurationManager $configurationManager,
        NestedArray $nestedArray
    ) {
        $this->consoleRoot = $consoleRoot;
        $this->configurationManager = $configurationManager;
        $this->nestedArray = $nestedArray;
        parent::__construct();
    }


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

        $languages = $this->configurationManager->getConfiguration()->get('application.languages');
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

        $pendingTranslations = $this->determinePendingTranslation($io, $language, $languages, $file);

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

    protected function determinePendingTranslation($io, $language = null, $languages, $fileFilter)
    {
        $englishFilesFinder = new Finder();
        $yaml = new Parser();
        $statistics = [];

        $englishDirectory = $this->consoleRoot .
            sprintf(
                DRUPAL_CONSOLE_LANGUAGE,
                'en'
            );

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
                $languageDir = $this->consoleRoot .
                                        sprintf(
                                            DRUPAL_CONSOLE_LANGUAGE,
                                            $langCode
                                        );
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
                $diff = $this->nestedArray->arrayDiff($englishFileParsed, $resourceTranslatedParsed, true, $diffStatistics);

                if (!empty($diff)) {
                    $diffFlatten = [];
                    $keyFlatten = '';
                    $this->nestedArray->yamlFlattenArray($diff, $diffFlatten, $keyFlatten);

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
