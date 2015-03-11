<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\SiteStatusCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
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

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
          ->setName('site:status')
          ->setDescription($this->trans('commands.site.status.description'))
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $systemManager = $this->getSystemManager();
        $requirements = $systemManager->listRequirements();

        $table = $this->getHelperSet()->get('table');

        $table->setlayout($table::LAYOUT_COMPACT);

        $table->addRow([
          sprintf(
            '<comment>%s</comment>',
            $this->trans('commands.site.status.messages.application')
          ),
          null
        ]);
        $table->addRow([
          'Drupal Console',
          $this->getApplication()->getVersion()
        ]);
        $table->addRow([null, null]);

        $table->addRow([
          sprintf(
            '<comment>%s</comment>',
            $this->trans('commands.site.status.messages.system')
          ),
          null
        ]);

        foreach ($requirements as $requirement) {
            $table->addRow([
              $requirement['title'],
              $requirement['value']
            ]);
        }
        $table->addRow([null, null]);

        $table->addRow([
          sprintf(
            '<comment>%s</comment>',
            $this->trans('commands.site.status.messages.database')
          ),
          null
        ]);

        $connectionInfo = $this->getConnectionInfo();

        foreach ($this->connectionInfoKeys as $connectionInfoKey) {
            $table->addRow([
              $this->trans('commands.site.status.messages.'.$connectionInfoKey),
              $connectionInfo['default'][$connectionInfoKey]
            ]);
        }

        $table->addRow([
          $this->trans('commands.site.status.messages.connection'),
          sprintf(
            '%s//%s:%s@%s%s/%s',
            $connectionInfo['default']['driver'],
            $connectionInfo['default']['username'],
            $connectionInfo['default']['password'],
            $connectionInfo['default']['host'],
            $connectionInfo['default']['port'] ? ':'. $connectionInfo['default']['port'] :'',
            $connectionInfo['default']['database']
          )
        ]);

        $table->addRow([null, null]);
        $table->addRow([
          sprintf(
            '<comment>%s</comment>',
            $this->trans('commands.site.status.messages.themes')
          ),
          null
        ]);

        $themes = $this->getThemesInfo();
        foreach ($themes as $key => $theme) {
            $table->addRow([
              $this->trans('commands.site.status.messages.'.$key),
              $theme
            ]);
        }

        $table->addRow([null, null]);
        $table->addRow([
          sprintf(
            '<comment>%s</comment>',
            $this->trans('commands.site.status.messages.directories')
          ),
          null
        ]);

        $directories = $this->getDirectoriesInfo();
        foreach ($directories as $key => $directory) {
            $table->addRow([
              $this->trans('commands.site.status.messages.'.$key),
              $directory
            ]);
        }

        $table->render($output);
    }

    protected function getThemesInfo()
    {
        $configFactory = $this->getConfigFactory();
        $config = $configFactory->get('system.theme');

        return [
          'theme_default' => $config->get('default'),
          'theme_admin' => $config->get('admin')
        ];
    }

    protected function getDirectoriesInfo()
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
            'directory_root' => $drupal_root,
            'directory_temporary' => $systemFile->get('path.temporary'),
            'directory_theme_default' => '/'. $themeDefault->getpath(),
            'directory_theme_admin' => $themeAdmin->getpath(),
        ];
    }
}
