<?php

/**
 * @file
 * Contains \Drupal\Console\Command\MigrateDebugCommand.
 */

namespace Drupal\Console\Command\Locale;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Console\Command\Shared\LocaleTrait;
use Drupal\Console\Utils\Site;
use Drupal\Console\Extension\Manager;
use Drupal\Console\Annotations\DrupalCommand;

/**
 * @DrupalCommand(
 *     extension = "locale",
 *     extensionType = "module"
 * )
 */
class TranslationStatusCommand extends Command
{
    use LocaleTrait;

    /**
      * @var Site
      */
    protected $site;

     /**
      * @var Manager
      */
    protected $extensionManager;

    /**
     * TranslationStatusCommand constructor.
     *
     * @param Site    $site
     * @param Manager $extensionManager
     */
    public function __construct(
        Site $site,
        Manager $extensionManager
    ) {
        $this->site = $site;
        $this->extensionManager = $extensionManager;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('locale:translation:status')
            ->setDescription($this->trans('commands.locale.translation.status.description'))
            ->addArgument(
                'language',
                InputArgument::OPTIONAL,
                $this->trans('commands.locale.translation.status.arguments.language')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $language = $input->getArgument('language');
        $tableHeader = [
            $this->trans('commands.locale.translation.status.messages.project'),
            $this->trans('commands.locale.translation.status.messages.version'),
            $this->trans('commands.locale.translation.status.messages.local-age'),
            $this->trans('commands.locale.translation.status.messages.remote-age'),
            $this->trans('commands.locale.translation.status.messages.info'),
        ];

        $locale = $this->extensionManager->getModule('locale');
        $this->site->loadLegacyFile($locale->getPath(true) . '/locale.compare.inc');

        $languages = locale_translatable_language_list();
        $status = locale_translation_get_status();

        if (!$languages) {
            $io->info($this->trans('commands.locale.translation.status.messages.no-languages'));
            return 1;
        }

        if (empty($status)) {
            $io->info($this->trans('commands.locale.translation.status.messages.no-translations'));
            return 1;
        }

        if ($languages) {
            $projectsStatus = $this->projectsStatus();

            foreach ($projectsStatus as $langcode => $rows) {
                $tableRows = [];
                if ($language !='' && !($language == $langcode || strtolower($language) == strtolower($languages[$langcode]->getName()))) {
                    continue;
                }
                $io->info($languages[$langcode]->getName());
                foreach ($rows as $row) {
                    if ($row[0] == 'drupal') {
                        $row[0] = $this->trans('commands.common.messages.drupal-core');
                    }
                    $tableRows[] = $row;
                }
                $io->table($tableHeader, $tableRows, 'compact');
            }
        }

        return 0;
    }
}
