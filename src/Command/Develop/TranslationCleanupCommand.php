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
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Utils\ConfigurationManager;

class TranslationCleanupCommand extends Command
{
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
     * TranslationCleanupCommand constructor.
     *
     * @param $consoleRoot
     * @param configurationManager $configurationManager
     */
    public function __construct(
        $consoleRoot,
        ConfigurationManager $configurationManager
    ) {
        $this->consoleRoot = $consoleRoot;
        $this->configurationManager = $configurationManager;
        parent::__construct();
    }

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

        $languages = $this->configurationManager->getConfiguration()->get('application.languages');
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

        $this->cleanupTranslations($io, $language, $languages);

        $io->success(
            $this->trans('commands.translation.cleanup.messages.success')
        );
    }

    protected function cleanupTranslations($io, $language = null, $languages)
    {
        $finder = new Finder();

        foreach ($languages as $langCode => $languageName) {
            if (file_exists($this->consoleRoot . sprintf(DRUPAL_CONSOLE_LANGUAGE, $langCode))) {
                foreach ($finder->files()->name('*.yml')->in($this->consoleRoot . sprintf(DRUPAL_CONSOLE_LANGUAGE, $langCode)) as $file) {
                    $filename = $file->getBasename('.yml');
                    if (!file_exists($this->consoleRoot . sprintf(DRUPAL_CONSOLE_LANGUAGE, 'en') . $filename . '.yml')) {
                        $io->info(
                            sprintf(
                                $this->trans('commands.translation.cleanup.messages.file-deleted'),
                                $filename,
                                $languageName
                            )
                        );
                        unlink($this->consoleRoot . sprintf(DRUPAL_CONSOLE_LANGUAGE, $langCode). '/' . $filename . '.yml');
                    }
                }
            }
        }
    }
}
