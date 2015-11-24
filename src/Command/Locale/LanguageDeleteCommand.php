<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Locale\LanguageDeleteDebugCommand.
 */

namespace Drupal\Console\Command\Locale;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Command\Locale\LocaleTrait;

class LanguageDeleteCommand extends ContainerAwareCommand
{
    use LocaleTrait;

    protected function configure()
    {
        $this
            ->setName('locale:language:delete')
            ->setDescription($this->trans('commands.locale.language.delete.description'))
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

        $languagesObjects = locale_translatable_language_list();
        $languages = $this->getLanguages();

        if (isset($languages[$language])) {
            $languageEntity = $languages[$language];
        } elseif (array_search($language, $languages)) {
            $langcode = array_search($language, $languages);
            $languageEntity = $languagesObjects[$langcode];
        } else {
            $messageHelper->addErrorMessage(
                sprintf(
                    $this->trans('commands.locale.language.delete.messages.invalid-language'),
                    $language
                )
            );
            return;
        }

        try {
            $configurable_language_storage = $this->getEntityManager()->getStorage('configurable_language');
            $language = $configurable_language_storage->load($languageEntity->getId())->delete();

            $messageHelper->addinfoMessage(
                sprintf(
                    $this->trans('commands.locale.language.delete.messages.language-deleted-sucessfully'),
                    $languageEntity->getName()
                )
            );
        } catch (\Exception $e) {
            $messageHelper->addErrorMessage(
                $e->getMessage()
            );
        }
    }
}
