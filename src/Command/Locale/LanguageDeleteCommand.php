<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Locale\LanguageDeleteDebugCommand.
 */

namespace Drupal\Console\Command\Locale;

use Drupal\Console\Style\DrupalStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\LocaleTrait;
use Drupal\Console\Command\Shared\CommandTrait;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Console\Utils\DrupalApi;
use Drupal\Console\Annotations\DrupalCommand;

/**
 * @DrupalCommand(
 *     extension = "locale",
 *     extensionType = "module"
 * )
 */
class LanguageDeleteCommand extends Command
{
    use CommandTrait;
    use LocaleTrait;

    /**
     * @var EntityTypeManager
     */
    protected $entityTypeManager;

    /**
     * @var ModuleHandlerInterface
     */
    protected $moduleHandler;

    /**
      * @var DrupalApi
      */
    protected $drupalApi;

    /**
     * LoginUrlCommand constructor.
     * @param EntityTypeManager    $entityTypeManager
     */
    public function __construct(
        EntityTypeManager $entityTypeManager,
        ModuleHandlerInterface $moduleHandler,
        DrupalApi $drupalApi
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->moduleHandler = $moduleHandler;
        $this->drupalApi = $drupalApi;
        parent::__construct();
    }

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
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $moduleHandler = $this->moduleHandler;
        $moduleHandler->loadInclude('locale', 'inc', 'locale.translation');
        $moduleHandler->loadInclude('locale', 'module');

        $language = $input->getArgument('language');

        $languagesObjects = locale_translatable_language_list();
        $languages = $this->getLanguages();

        if (isset($languagesObjects[$language])) {
            $languageEntity = $languagesObjects[$language];
        } elseif (array_search($language, $languages)) {
            $langcode = array_search($language, $languages);
            $languageEntity = $languagesObjects[$langcode];
        } else {
            $io->error(
                sprintf(
                    $this->trans('commands.locale.language.delete.messages.invalid-language'),
                    $language
                )
            );
            return;
        }

        try {
            $configurable_language_storage = $this->entityTypeManager->getStorage('configurable_language');
            $configurable_language_storage->load($languageEntity->getId())->delete();

            $io->info(
                sprintf(
                    $this->trans('commands.locale.language.delete.messages.language-deleted-successfully'),
                    $languageEntity->getName()
                )
            );
        } catch (\Exception $e) {
            $io->error($e->getMessage());
        }
    }
}
