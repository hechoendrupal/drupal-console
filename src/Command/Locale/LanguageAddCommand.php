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
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Command\Locale\LocaleTrait;

class LanguageAddCommand extends ContainerAwareCommand
{
    use LocaleTrait;

    protected function configure()
    {
        $this
            ->setName('locale:language:add')
            ->setDescription($this->trans('commands.locale.language.add.description'))
            ->addArgument(
                'language',
                InputArgument::REQUIRED,
                $this->trans('commands.locale.translation.status.arguments.language')
            );

        $this->addDependency('locale');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $messageHelper = $this->getMessageHelper();
        $moduleHandler = $this->getModuleHandler();
        $moduleHandler->loadInclude('locale', 'inc', 'locale.translation');
        $moduleHandler->loadInclude('locale', 'module');

        $language = $input->getArgument('language');

        $languages = $this->getLanguages();


        if (isset($languages[$language])) {
            $langcode = $language;
        } elseif (array_search($language, $languages)) {
            $langcode = array_search($language, $languages);
        } else {
            $messageHelper->addErrorMessage(
                sprintf(
                    $this->trans('commands.locale.language.add.messages.invalid-language'),
                    $language
                )
            );
            return;
        }

        try {
            $language = ConfigurableLanguage::createFromLangcode($langcode);
            $language->type = LOCALE_TRANSLATION_REMOTE;
            $language->save();

            $messageHelper->addinfoMessage(
                sprintf(
                    $this->trans('commands.locale.language.add.messages.language-add-sucessfully'),
                    $language->getName()
                )
            );
        } catch (\Exception $e) {
            $messageHelper->addErrorMessage(
                $e->getMessage()
            );
        }
    }
}
