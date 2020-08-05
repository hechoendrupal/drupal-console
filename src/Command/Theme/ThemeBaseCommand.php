<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Theme\ThemeBaseCommand.
 */

namespace Drupal\Console\Command\Theme;

use Drupal\Console\Core\Command\Command;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\UnmetDependenciesException;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Utils\Site;
use Drupal\Console\Utils\Validator;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Console\Utils\DrupalApi;
use Drupal\Console\Extension\Manager;
/**
 * Class ThemeBaseCommand
 *
 * @package Drupal\Console\Command\Theme
 */
class ThemeBaseCommand extends Command
{
    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var ThemeHandler
     */
    protected $themeHandler;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;
    /**
     * @var Site
     */
    protected $site;
    /**
     * @var Validator
     */
    protected $validator;
     /**
     * @var ModuleInstaller
     */
    protected $moduleInstaller;
    /**
     * @var DrupalApi
     */
    protected $drupalApi;
    /**
     * @var Manager
     */
    protected $extensionManager;
    /**
     * @var string
     */
    protected $appRoot;
    /**
     * @var array
     */
    protected $themes;
    /**
     * @var array
     */
    protected $availableThemes = [];

    /**
     * @var array
     */
    protected $unavailableThemes = [];

    /**
     * @var array
     */
    protected $uninstalledThemes = [];

    /**
     * DebugCommand constructor.
     *
     * @param ConfigFactory $configFactory
     * @param ThemeHandler  $themeHandler
     * @param ChainQueue    $chainQueue
     * @param Site          $site
     * @param Validator     $validator
     * @param ModuleInstaller $moduleInstaller
     * @param DrupalApi       $drupalApi
     * @param Manager         $extensionManager
     * @param $appRoot
     */
    public function __construct(
        ConfigFactoryInterface $configFactory,
        ThemeHandlerInterface $themeHandler,
        ChainQueue $chainQueue,
        Site $site,
        Validator $validator,
        ModuleInstallerInterface $moduleInstaller,
        DrupalApi $drupalApi,
        Manager $extensionManager, $appRoot
    ) {
        $this->configFactory = $configFactory;
        $this->themeHandler = $themeHandler;
        $this->chainQueue = $chainQueue;
        $this->site = $site;
        $this->validator = $validator;
        $this->moduleInstaller = $moduleInstaller;
        $this->drupalApi = $drupalApi;
        $this->extensionManager = $extensionManager;
        $this->appRoot = $appRoot;
        $this->themes = $this->themeHandler->rebuildThemeData();
        parent::__construct();
    }

    /**
     * Gets the list of themes available on the website.
     *
     * @return array themes from site.
     */
    public function getThemes()
    {
        return $this->themes;
    }

    /**
     * Gets unavailable themes.
     *
     * @return array
     *   Available themes from input.
     */
    public function getAvailableThemes()
    {
        return $this->availableThemes;
    }

    /**
     * Gets unavailable themes.
     *
     * @return array
     *   Unavailable themes from input.
     */
    public function getUnavailableThemes()
    {
        return $this->unavailableThemes;
    }

    /**
     * Gets uninstalled themes.
     *
     * @return array
     *  Uninstalled themes from input.
     */
    public function getUninstalledThemes()
    {
        return $this->uninstalledThemes;
    }

    /**
     * Adds available theme.
     *
     * @param string $themeMachineName
     *   Theme machine name.
     * @param string $themeName
     *   Theme name.
     */
    public function addAvailableTheme($themeMachineName, $themeName)
    {
        $this->availableThemes[$themeMachineName] = $themeName;
    }

    /**
     * Adds unavailable theme.
     *
     * @param string $themeMachineName
     *   Theme machine name.
     * @param string $themeName
     *   Theme name.
     */
    public function addUnavailableTheme($themeMachineName, $themeName)
    {
        $this->unavailableThemes[$themeMachineName] = $themeName;
    }

    /**
     * Adds uninstall theme.
     *
     * @param string $themeMachineName
     *   Theme machine name.
     * @param string $themeName
     *   Theme name.
     */
    public function addUninstalledTheme($themeMachineName, $themeName)
    {
        $this->uninstalledThemes[$themeMachineName] = $themeName;
    }

    /**
     * Prepare theme arrays: available, unavailable, uninstalled.
     *
     * @param array $themes
     *   Themes passed from a user.
     */
    protected function prepareThemesArrays($themes)
    {
        $siteThemes = $this->getThemes();
        foreach ($themes as $themeName) {
            if (isset($siteThemes[$themeName]) && $siteThemes[$themeName]->status == 1) {
                $this->addAvailableTheme($themeName, $siteThemes[$themeName]->info['name']);
            } elseif (isset($siteThemes[$themeName]) && $siteThemes[$themeName]->status == 0) {
                $this->addUninstalledTheme($themeName, $siteThemes[$themeName]->info['name']);
            } else {
                $this->addUnavailableTheme($themeName, $themeName);
            }
        }
    }

    /**
     * Gets list of themes for autocomplete based on status.
     *
     * @param int $status
     *   Status of the themes.
     *
     * @return array
     *   Themes list.
     */
    protected function getAutocompleteList($status = 1)
    {
        $theme_list = [];
        foreach ($this->getThemes() as $theme_id => $theme) {
            if (!empty($theme->info['hidden'])) {
                continue;
            }

            if (!empty($theme->status == $status)) {
                continue;
            }
            $theme_list[$theme_id] = $theme->getName();
        }

        return $theme_list;
    }

    /**
     * Sets message of type 'info'.
     *
     * @param string $translationString
     *   String which will be replaced with translation.
     * @param string $value
     *   The value to be include into the string.
     */
    protected function setInfoMessage($translationString, $value)
    {
        $this->setMessage('info', $translationString, $value);
    }

    /**
     * Sets message of type 'error'.
     *
     * @param string $translationString
     *   String which will be replaced with translation.
     * @param string $value
     *   The value to be include into the string.
     */
    protected function setErrorMessage($translationString, $value)
    {
        $this->setMessage('error', $translationString, $value);
    }

    /**
     * Sets message in Drupal Console.
     *
     * @param string $type
     *   Type of the message: info, error and etc.
     * @param string $translationString
     *   String which will be replaced with translation.
     * @param string $value
     *   The value to be include into the string.
     */
    protected function setMessage($type, $translationString, $value)
    {
        $text = is_array($value) ? implode(',', $value) : $value;
        $this->getIo()->{$type}(
            sprintf(
                $this->trans($translationString),
                $text
            )
        );
    }


  /**
   * @param string $title
   * @param string $question
   * @param array $theme_list
   */
    protected function getThemeArgument($title, $question, $theme_list) {
      $input = $this->getIo()->getInput();
      $theme = $input->getArgument('theme');

      if (!$theme) {
        $this->getIo()->info($this->trans($title));
        $theme_list_install = [];
        while (TRUE) {
          $theme_name = $this->getIo()->choiceNoList(
            $this->trans($question),
            array_keys($theme_list),
            '',
            TRUE
          );

          if (empty($theme_name) || is_numeric($theme_name)) {
            break;
          }

          $theme_list_install[] = $theme_name;

          if (array_search($theme_name, $theme_list_install, TRUE) >= 0) {
            unset($theme_list[$theme_name]);
          }
        }
      }
    }

}
