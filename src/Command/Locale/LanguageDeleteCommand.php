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

use Drupal\Core\Entity\EntityTypeManager;

/**
 * @DrupalCommand(
 *     dependencies = {
 *         "locale"
 *     }
 * )
 */
class LanguageDeleteCommand extends Command
{
    use CommandTrait;
    use ContainerAwareCommandTrait;
    use LocaleTrait;

    /**
     * @var EntityTypeManager
     */
    protected $entityTypeManager;

    /**
     * LoginUrlCommand constructor.
     * @param EntityTypeManager    $entityTypeManager
     */
    public function __construct(
        EntityTypeManager $entityTypeManager
    ) {
        $this->entityTypeManager = $entityTypeManager;
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
        $moduleHandler = $this->getModuleHandler();
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
