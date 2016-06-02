<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Site\StatusCommand.
 */

namespace Drupal\Console\Command\Site;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Core\Database\Database;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 *  This command provides a report of the current drupal installation.
 *
 *  @category site
 */
class StatusCommand extends Command
{
    use ContainerAwareCommandTrait;

    /* @var $connectionInfoKeys array */
    protected $connectionInfoKeys = [
      'driver',
      'host',
      'database',
      'port',
      'username',
      'password',
    ];

    protected $groups = [
      'system',
      'database',
      'theme',
      'directory',
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('site:status')
            ->setDescription($this->trans('commands.site.status.description'))
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.site.status.options.format'),
                'table'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $systemData = $this->getSystemData();
        $connectionData = $this->getConnectionData();
        $themeInfo = $this->getThemeData();
        $directoryData = $this->getDirectoryData();

        $siteData = array_merge(
            $systemData,
            $connectionData,
            $themeInfo,
            $directoryData
        );

        $format = $input->getOption('format');

        if ('table' === $format) {
            $this->showDataAsTable($io, $siteData);
        }

        if ('json' === $format) {
            $output->writeln(json_encode($siteData, JSON_PRETTY_PRINT));
        }
    }

    protected function getSystemData()
    {
        $systemManager = $this->getDrupalService('system.manager');
        if (!$systemManager) {
            return [];
        }

        $requirements = $systemManager->listRequirements();
        $systemData = [];

        foreach ($requirements as $key => $requirement) {
            if ($requirement['title'] instanceof \Drupal\Core\StringTranslation\TranslatableMarkup) {
                $title = $requirement['title']->render();
            } else {
                $title = $requirement['title'];
            }

            $systemData['system'][$title] = $requirement['value'];
        }

        if ($settings = $this->getDrupalService('settings')) {
            try {
                $hashSalt = $settings->getHashSalt();
            } catch (\Exception $e) {
                $hashSalt = '';
            }
            $systemData['system'][$this->trans('commands.site.status.messages.hash_salt')] = $hashSalt;
            $systemData['system'][$this->trans('commands.site.status.messages.console')] = $this->getApplication()->getVersion();
        }

        return $systemData;
    }

    protected function getConnectionData()
    {
        $connectionInfo = Database::getConnectionInfo();

        $connectionData = [];
        foreach ($this->connectionInfoKeys as $connectionInfoKey) {
            if ("password" == $connectionInfoKey) {
                continue;
            }

            $connectionKey = $this->trans('commands.site.status.messages.'.$connectionInfoKey);
            $connectionData['database'][$connectionKey] = $connectionInfo['default'][$connectionInfoKey];
        }

        if ($connectionInfo['default']['password']) {
            $connectionInfo['default']['password'] = str_repeat("*", strlen($connectionInfo['default']['password']));
        }

        $connectionData['database'][$this->trans('commands.site.status.messages.connection')] = sprintf(
            '%s//%s:%s@%s%s/%s',
            $connectionInfo['default']['driver'],
            $connectionInfo['default']['username'],
            $connectionInfo['default']['password'],
            $connectionInfo['default']['host'],
            $connectionInfo['default']['port'] ? ':'.$connectionInfo['default']['port'] : '',
            $connectionInfo['default']['database']
        );

        return $connectionData;
    }

    protected function getThemeData()
    {
        $configFactory = $this->getDrupalService('config.factory');
        $config = $configFactory->get('system.theme');

        return [
          'theme' => [
            'theme_default' => $config->get('default'),
            'theme_admin' => $config->get('admin'),
          ],
        ];
    }

    protected function getDirectoryData()
    {
        $drupal = $this->get('site');
        $drupal_root = $drupal->getRoot();

        $configFactory = $this->getDrupalService('config.factory');
        $systemTheme = $configFactory->get('system.theme');

        $themeDefaultDirectory = '';
        $themeAdminDirectory = '';
        try {
            $themeHandler = $this->getDrupalService('theme_handler');
            $themeDefault = $themeHandler->getTheme(
                $systemTheme->get('default')
            );
            $themeDefaultDirectory = sprintf('/%s', $themeDefault->getpath());

            $themeAdmin = $themeHandler->getTheme(
                $systemTheme->get('admin')
            );
            $themeAdminDirectory = sprintf('/%s', $themeAdmin->getpath());
        } catch (\Exception $e) {
        }

        $systemFile = $this->getDrupalService('config.factory')
            ->get('system.file');

        return [
          'directory' => [
            $this->trans('commands.site.status.messages.directory_root') => $drupal_root,
            $this->trans('commands.site.status.messages.directory_temporary') => $systemFile->get('path.temporary'),
            $this->trans('commands.site.status.messages.directory_theme_default') => $themeDefaultDirectory,
            $this->trans('commands.site.status.messages.directory_theme_admin') => $themeAdminDirectory,
          ],
        ];
    }

    protected function showDataAsTable(DrupalStyle $io, $siteData)
    {
        if (empty($siteData)) {
            return [];
        }
        $io->newLine();
        foreach ($this->groups as $group) {
            $tableRows = [];
            $groupData = $siteData[$group];
            $io->comment($this->trans('commands.site.status.messages.'.$group));

            foreach ($groupData as $key => $item) {
                $tableRows[] = [$key, $item];
            }

            $io->table([], $tableRows, 'compact');
        }
    }
}
