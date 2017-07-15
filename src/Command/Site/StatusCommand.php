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
use Drupal\Console\Core\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\system\SystemManager;
use Drupal\Core\Site\Settings;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ThemeHandler;

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
     * @var SystemManager
     */
    protected $systemManager;

    /**
     * @var Settings
     */
    protected $settings;

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var ThemeHandler
     */
    protected $themeHandler;

    /**
     * @var string
     */
    protected $appRoot;

    /**
     * DebugCommand constructor.
     *
     * @param SystemManager $systemManager
     * @param Settings      $settings
     * @param ConfigFactory $configFactory
     * @param ThemeHandler  $themeHandler
     * @param $appRoot
     */
    public function __construct(
        SystemManager $systemManager,
        Settings $settings,
        ConfigFactory $configFactory,
        ThemeHandler $themeHandler,
        $appRoot
    ) {
        $this->systemManager = $systemManager;
        $this->settings = $settings;
        $this->configFactory = $configFactory;
        $this->themeHandler = $themeHandler;
        $this->appRoot = $appRoot;
        parent::__construct();
    }

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
            )
            ->setAliases(['ss']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Make sure all modules are loaded.
        $this->container->get('module_handler')->loadAll();

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
        if (!$this->systemManager) {
            return [];
        }

        $requirements = $this->systemManager->listRequirements();
        $systemData = [];

        foreach ($requirements as $key => $requirement) {
            if ($requirement['title'] instanceof \Drupal\Core\StringTranslation\TranslatableMarkup) {
                $title = $requirement['title']->render();
            } else {
                $title = $requirement['title'];
            }

            $systemData['system'][$title] = strip_tags($requirement['value']);
        }

        if ($this->settings) {
            try {
                $hashSalt = $this->settings->getHashSalt();
            } catch (\Exception $e) {
                $hashSalt = '';
            }
            $systemData['system'][$this->trans('commands.site.status.messages.hash-salt')] = $hashSalt;
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
        $config = $this->configFactory->get('system.theme');

        return [
          'theme' => [
            'theme_default' => $config->get('default'),
            'theme_admin' => $config->get('admin'),
          ],
        ];
    }

    protected function getDirectoryData()
    {
        $systemTheme = $this->configFactory->get('system.theme');

        $themeDefaultDirectory = '';
        $themeAdminDirectory = '';
        try {
            $themeDefault = $this->themeHandler->getTheme(
                $systemTheme->get('default')
            );
            $themeDefaultDirectory = sprintf('/%s', $themeDefault->getpath());

            $themeAdmin = $this->themeHandler->getTheme(
                $systemTheme->get('admin')
            );
            $themeAdminDirectory = sprintf('/%s', $themeAdmin->getpath());
        } catch (\Exception $e) {
        }

        $systemFile = $this->configFactory->get('system.file');

        return [
          'directory' => [
            $this->trans('commands.site.status.messages.directory-root') => $this->appRoot,
            $this->trans('commands.site.status.messages.directory-temporary') => $systemFile->get('path.temporary'),
            $this->trans('commands.site.status.messages.directory-theme-default') => $themeDefaultDirectory,
            $this->trans('commands.site.status.messages.directory-theme-admin') => $themeAdminDirectory,
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
