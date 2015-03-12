<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\SiteStatusCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *  This command provides a view of the current drupal installation.
 *
 *  @category site
 */
class SiteStatusCommand extends ContainerAwareCommand
{

    /* @var $connectionInfoKeys array */
    protected $connectionInfoKeys = [
      'driver',
      'host',
      'database',
      'port',
      'username',
      'password'
    ];

    protected $groups = [
      'system',
      'database',
      'theme',
      'directory'
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
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $systemData = $this->getSystemData();
        $connectionData = $this->getConnectionData();
        $themeInfo = $this->getThemeData();
        $directoryData = $this->getDirectoryData();

        $siteData = array_merge($systemData, $connectionData, $themeInfo, $directoryData);

        $format = $input->getOption('format');

        if ('table' === $format) {
            $this->showDataAsTable($output, $siteData);
        }

        if ('json' === $format) {
            $output->writeln(json_encode($siteData, JSON_PRETTY_PRINT));
        }
    }

    protected function getSystemData()
    {
        $systemManager = $this->getSystemManager();
        $requirements = $systemManager->listRequirements();
        $requirementData = [];

        foreach ($requirements as $key => $requirement) {
            $requirementData['system'][$requirement['title']] = $requirement['value'];
        }

        return $requirementData;
    }

    protected function getConnectionData()
    {
        $connectionInfo = $this->getConnectionInfo();
        $connectionData = [];

        foreach ($this->connectionInfoKeys as $connectionInfoKey) {
            $connectionKey = $this->trans('commands.site.status.messages.'.$connectionInfoKey);
            $connectionData['database'][$connectionKey] = $connectionInfo['default'][$connectionInfoKey];
        }

        $connectionData['database'][$this->trans('commands.site.status.messages.connection')] = sprintf(
          '%s//%s:%s@%s%s/%s',
          $connectionInfo['default']['driver'],
          $connectionInfo['default']['username'],
          $connectionInfo['default']['password'],
          $connectionInfo['default']['host'],
          $connectionInfo['default']['port'] ? ':'. $connectionInfo['default']['port'] :'',
          $connectionInfo['default']['database']
        );

        return $connectionData;
    }

    protected function getThemeData()
    {
        $configFactory = $this->getConfigFactory();
        $config = $configFactory->get('system.theme');

        return [
          'theme' => [
            'theme_default' => $config->get('default'),
            'theme_admin' => $config->get('admin')
          ]
        ];
    }

    protected function getDirectoryData()
    {
        $drupalBootstrap = $this->getHelperSet()->get('bootstrap');
        $drupal_root = $drupalBootstrap->getDrupalRoot();

        $configFactory = $this->getConfigFactory();
        $systemTheme = $configFactory->get('system.theme');

        $themeHandler = $this->getThemeHandler();
        $themeDefault = $themeHandler->getTheme($systemTheme->get('default'));
        $themeAdmin = $themeHandler->getTheme($systemTheme->get('admin'));

        $systemFile = $this->getConfigFactory()->get('system.file');

        return [
          'directory' => [
            $this->trans('commands.site.status.messages.directory_root') => $drupal_root,
            $this->trans('commands.site.status.messages.directory_temporary') => $systemFile->get('path.temporary'),
            $this->trans('commands.site.status.messages.directory_theme_default') => '/'. $themeDefault->getpath(),
            $this->trans('commands.site.status.messages.directory_theme_admin') => '/' . $themeAdmin->getpath(),
          ]
        ];
    }

    protected function showDataAsTable($output, $siteData)
    {
        if (empty($siteData)) {
            return [];
        }

        $table = $this->getHelperSet()->get('table');
        $table->setlayout($table::LAYOUT_COMPACT);
        foreach ($this->groups as $group) {
            $groupData = $siteData[$group];
            $table->addRow([
              sprintf(
                '<comment>%s</comment>',
                $this->trans('commands.site.status.messages.'.$group)
              ),
              null
            ]);

            foreach ($groupData as $key => $item) {
                $table->addRow([
                  $key,
                  $item
                ]);
            }
        }

        $table->render($output);
    }
}
