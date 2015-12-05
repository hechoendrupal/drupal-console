<?php

/**
 * @file
 * Contains \Drupal\Console\Command\MigrateDebugCommand.
 */

namespace Drupal\Console\Command\Locale;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Drupal\Console\Command\ContainerAwareCommand;

class TranslationStatusCommand extends ContainerAwareCommand
{
    use LocaleTrait;

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

        $this->addDependency('locale');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $language = $input->getArgument('language');

        $table = new Table($output);
        $table->setStyle('compact');

        $this->displayUpdates($language, $output, $table);
    }

    protected function displayUpdates($language_filter, $output, $table)
    {
        $table->setHeaders(
            [
                $this->trans('commands.locale.translation.status.messages.project'),
                $this->trans('commands.locale.translation.status.messages.version'),
                $this->trans('commands.locale.translation.status.messages.local-age'),
                $this->trans('commands.locale.translation.status.messages.remote-age'),
                $this->trans('commands.locale.translation.status.messages.info'),
            ]
        );

        $languages = locale_translatable_language_list();
        $status = locale_translation_get_status();

        $this->getModuleHandler()->loadInclude('locale', 'compare.inc');

        if (!$languages) {
            $output->writeln('[+] <info>'.$this->trans('commands.locale.translation.status.messages.no-languages') .'</info>');

            return;
        } elseif (empty($status)) {
            $output->writeln('[+] <info>'.$this->trans('commands.locale.translation.status.messages.no-translations') .'</info>');
            return;
        }

        if ($languages) {
            $table->setStyle('compact');

            $projectsStatus = $this->projectsStatus();

            foreach ($projectsStatus as $langcode => $rows) {
                $table->setRows(array());
                if ($language_filter !='' && !($language_filter == $langcode || strtolower($language_filter) == strtolower($languages[$langcode]->getName()))) {
                    continue;
                }
                $output->writeln('[+] <info>'.$languages[$langcode]->getName() .'</info>');
                foreach ($rows as $row) {
                    if ($row[0] == 'drupal') {
                        $row[0] = $this->trans('commands.common.messages.drupal-core');
                    }
                    $table->addRow($row);
                }
                $table->render();
            }
        }
    }
}
