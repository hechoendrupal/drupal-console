<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Theme\InstallCommand.
 */

namespace Drupal\Console\Command\Theme;

use Drupal\Console\Core\Command\Command;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\UnmetDependenciesException;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Console\Core\Utils\ChainQueue;

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
     */
    public function __construct(
        ConfigFactoryInterface $configFactory,
        ThemeHandlerInterface $themeHandler,
        ChainQueue $chainQueue
    ) {
        $this->configFactory = $configFactory;
        $this->themeHandler = $themeHandler;
        $this->chainQueue = $chainQueue;
        $this->themes = $this->themeHandler->rebuildThemeData();
        parent::__construct();
    }

    /***
     * @return array themes from site.
     */
    public function getThemes() {
        return $this->themes;
    }

    /***
     * @return array available themes from site.
     */
    public function getAvailableThemes() {
      return $this->availableThemes;
    }

    /***
     * @return array unavailable themes from site.
     */
    public function getUnavailableThemes() {
      return $this->unavailableThemes;
    }

    /***
     * @return array uninstalled themes from site.
     */
    public function getUninstalledThemes() {
      return $this->uninstalledThemes;
    }

    /***
     * @return array uninstalled themes from site.
     */
    public function getInstalledThemes() {
      return $this->uninstalledThemes;
    }

    /***
     * @param $themeMachineName
     * @param $themeName
     */
    public function addAvailableTheme($themeMachineName, $themeName) {
      $this->availableThemes[$themeMachineName] = $themeName;
    }

    /***
     * @param $themeMachineName
     * @param $themeName
     */
    public function addUnavailableTheme($themeMachineName, $themeName) {
      $this->unavailableThemes[$themeMachineName] = $themeName;
    }

    /***
     * @param $themeMachineName
     * @param $themeName
     */
    public function addUninstalledTheme($themeMachineName, $themeName) {
      $this->uninstalledThemes[$themeMachineName] = $themeName;
    }

    /**
     * @param $items
     * @return string
     */
    public function getImplodedString($items) {
      return implode(',', $items);
    }

    /**
     * @param array $themes
     */
    protected function prepareThemesArrays($themes) {
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
     * @param $status
     * @return array
     */
    protected function getAutocompleteList($status = 1) {
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
     * @param $translationString
     * @param $value
     */
    protected function setInfoMessage($translationString, $value) {
      $this->setMessage('info', $translationString, $value);
    }

    /**
     * @param $translationString
     * @param $value
     */
    protected function setErrorMessage($translationString, $value) {
      $this->setMessage('error', $translationString, $value);
    }

    /**
     * @param $type
     * @param $translationString
     * @param $value
     */
    protected function setMessage($type, $translationString, $value) {
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
        while (true) {
          $theme_name = $this->getIo()->choiceNoList(
            $this->trans($question),
            array_keys($theme_list),
            '',
            true
          );

          if (empty($theme_name) || is_numeric($theme_name)) {
            break;
          }

          $theme_list_install[] = $theme_name;

          if (array_search($theme_name, $theme_list_install, true) >= 0) {
            unset($theme_list[$theme_name]);
          }
        }

        $input->setArgument('theme', $theme_list_install);
      }
    }

}
