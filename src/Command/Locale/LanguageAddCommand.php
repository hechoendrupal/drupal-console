<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Locale\LanguageAddCommand.
 */

namespace Drupal\Console\Command\Locale;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Console\Utils\Site;
use Drupal\Console\Annotations\DrupalCommand;

/**
 * @DrupalCommand(
 *     extension = "locale",
 *     extensionType = "module"
 * )
 */
class LanguageAddCommand extends Command
{

    /**
     * @var Site
     */
    protected $site;

    /**
     * @var ModuleHandlerInterface
     */
    protected $moduleHandler;

    /**
     * @var array
     */
    protected $missingLangues = [];

    /**
     * LanguageAddCommand constructor.
     *
     * @param Site $site
     * @param ModuleHandlerInterface $moduleHandler
     */
    public function __construct(
      Site $site,
      ModuleHandlerInterface $moduleHandler
    ) {
        $this->site = $site;
        $this->moduleHandler = $moduleHandler;
        parent::__construct();
    }

    protected function configure()
    {
        $this
          ->setName('locale:language:add')
          ->setDescription($this->trans('commands.locale.language.add.description'))
          ->addArgument(
            'language',
            InputArgument::REQUIRED | InputArgument::IS_ARRAY,
            $this->trans('commands.locale.translation.status.arguments.language')
          );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $moduleHandler = $this->moduleHandler;
        $moduleHandler->loadInclude('locale', 'inc', 'locale.translation');
        $moduleHandler->loadInclude('locale', 'module');

        $languageArguments = $this->checkLanguages($input->getArgument('language'));
        $missingLanguages = $this->getMissingLangugaes();
        if (!empty($missingLanguages)) {
            $translatableString = count($missingLanguages) == 1 ? 'commands.locale.language.add.messages.invalid-language' : 'commands.locale.language.add.messages.invalid-languages';
            $this->getIo()->error(sprintf(
              $this->trans($translatableString),
              implode(', ', $missingLanguages)
            ));

            return 1;
        }

        try {
            $installedLanguages = [];
            foreach (array_keys($languageArguments) as $langcode) {
                if (!($language = ConfigurableLanguage::load($langcode))) {
                    $language = ConfigurableLanguage::createFromLangcode($langcode);
                    $language->type = LOCALE_TRANSLATION_REMOTE;
                    $language->save();
                } else {
                    $installedLanguages[] = $languageArguments[$langcode];
                    unset($languageArguments[$langcode]);
                }
            }

            if (!empty($languageArguments)) {
                $translatableString = count($languageArguments) == 1 ? 'commands.locale.language.add.messages.language-add-successfully' : 'commands.locale.language.add.messages.languages-add-successfully';
                $this->getIo()->info(sprintf(
                  $this->trans($translatableString),
                  implode(', ', $languageArguments)
                ));
            }

            if (!empty($installedLanguages)) {
                $translatableString = count($installedLanguages) == 1 ? 'commands.locale.language.add.messages.language-installed' : 'commands.locale.language.add.messages.languages-installed';
                $this->getIo()->note(sprintf(
                  $this->trans($translatableString),
                  implode(', ', $installedLanguages)
                ));
            }
        } catch (\Exception $e) {
            $this->getIo()->error($e->getMessage());

            return 1;
        }

        return 0;
    }

    /**
     * Checks the existance of the languages in the system.
     *
     * @param array $languageArguments
     *   List of language arguments.
     *
     * @return array
     *   List of available languages.
     */
    protected function checkLanguages($languageArguments)
    {
        $languages = $this->site->getStandardLanguages();
        $language_codes = array_keys($languages);
        $buildLanguages = [];
        foreach ($languageArguments as $language) {
            if (array_search($language, $language_codes)) {
                $buildLanguages[$language] = $languages[$language];
            } elseif ($language_code = array_search($language, $languages)) {
                $buildLanguages[$language_code] = $language;
            } else {
                $this->addMissingLanguage($language);
            }
        }
        return $buildLanguages;
    }

    /**
     * Add missing language.
     *
     * @param string $language
     *   Language code or name.
     */
    private function addMissingLanguage($language)
    {
        $this->missingLangues[] = $language;
    }

    /**
     * Get list of missing languages.
     *
     * @return array
     *   Missing languges.
     */
    private function getMissingLangugaes()
    {
        return $this->missingLangues;
    }
}
