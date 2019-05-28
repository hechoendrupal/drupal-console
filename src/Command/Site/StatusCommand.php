<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Site\StatusCommand.
 */

namespace Drupal\Console\Command\Site;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Core\Database\Database;
use Drupal\system\SystemManager;
use Drupal\Core\Site\Settings;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ThemeHandler;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 *  This command provides a report of the current drupal installation.
 *
 *  @category site
 */
class StatusCommand extends ContainerAwareCommand
{
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
     * A list of system requirements to be skipped from output.
     *
     * @var array
     */
    protected $systemDataSkipList = [
      // The PHP memory limit in CLI is different from the one available to the
      // web server. Skip to avoid confusion.
      'php_memory_limit',
      // The web server cannot be determined in CLI since Drupal takes it from
      // the $_SERVER variable in HTTP requests.
      'webserver',
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
     * @var RendererInterface
     */
    protected $renderer;

    /**
     * DebugCommand constructor.
     *
     * @param SystemManager     $systemManager
     * @param Settings          $settings
     * @param ConfigFactory     $configFactory
     * @param ThemeHandler      $themeHandler
     * @param $appRoot
     * @param RendererInterface $renderer
     */
    public function __construct(
        SystemManager $systemManager = null,
        Settings $settings,
        ConfigFactory $configFactory,
        ThemeHandler $themeHandler,
        $appRoot,
        RendererInterface $renderer
    ) {
        $this->systemManager = $systemManager;
        $this->settings = $settings;
        $this->configFactory = $configFactory;
        $this->themeHandler = $themeHandler;
        $this->appRoot = $appRoot;
        $this->renderer = $renderer;
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
            $this->showDataAsTable($siteData);
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
            if (in_array($key, $this->systemDataSkipList)) {
                continue;
            }

            if ($requirement['title'] instanceof TranslatableMarkup) {
                $title = $requirement['title']->render();
            } else {
                $title = $requirement['title'];
            }

            $value = !empty($requirement['value']) ? strip_tags($requirement['value']) : '';
            if (isset($requirement['severity'])) {
                switch ($requirement['severity']) {
                    case SystemManager::REQUIREMENT_ERROR:
                        $value = "<error>$value</error>";
                        break;

                    case SystemManager::REQUIREMENT_WARNING:
                        $value = "<comment>$value</comment>";
                        break;

                }
            }

            if ($this->getIo()->isVerbose()) {
                $description = !empty($requirement['description']) ? $requirement['description'] : null;
                if ($description instanceof TranslatableMarkup) {
                    $description = $description->render();
                }
                if (is_array($description)) {
                    $description = $this->renderer->renderPlain($description);
                }
                $value .= $description ? ' (' . strip_tags($description) . ')' : '';
            }

            $systemData['system'][strip_tags($title)] = $value;
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
        $has_password = FALSE;

        $connectionData = [];
        foreach ($this->connectionInfoKeys as $connectionInfoKey) {
            if ('password' == $connectionInfoKey) {
                $has_password = TRUE;
                continue;
            }

            if (!empty($connectionInfo['default'][$connectionInfoKey])) {
                $connectionKey = $this->trans('commands.site.status.messages.' . $connectionInfoKey);
                $connectionData['database'][$connectionKey] = $connectionInfo['default'][$connectionInfoKey];
            }
        }

        $connection_url = Database::getConnectionInfoAsUrl();
        $displayable_url = $has_password ? preg_replace('/(?<=:)([^@:]+)(?=@[^@]+$)/', '********', $connection_url, 1) : $connection_url;
        $connectionData['database'][$this->trans('commands.site.status.messages.connection')] = $displayable_url;

        return $connectionData;
    }

    protected function getThemeData()
    {
        $config = $this->configFactory->get('system.theme');

        return [
          'theme' => [
            $this->trans('commands.site.status.messages.theme-default') => $config->get('default'),
            $this->trans('commands.site.status.messages.theme-admin') => $config->get('admin'),
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

    protected function showDataAsTable($siteData)
    {
        if (empty($siteData)) {
            return [];
        }
        $this->getIo()->newLine();
        foreach ($this->groups as $group) {
            $tableRows = [];
            $groupData = $siteData[$group];
            $this->getIo()->comment($this->trans('commands.site.status.messages.'.$group));

            foreach ($groupData as $key => $item) {
                $tableRows[] = [$key, $item];
            }

            $this->getIo()->table([], $tableRows, 'compact');
        }
    }
}
