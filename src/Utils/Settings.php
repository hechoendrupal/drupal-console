<?php
namespace Drupal\Console\Utils;

use Drupal\Console\Utils\Site;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class Settings
 * @package Drupal\Console\Utils
 */
class Settings
{
    /* @var Site */
    protected $site;

    /* @var Settings */
    protected $settings;

    /**
     * @var Fs
     */
    protected $filesystem;

    /**
     * Process constructor.
     * @param Site $site
     */
    public function __construct(Site $site, Filesystem $filesystem)
    {
        $this->site = $site;
        $this->filesystem = $filesystem;
    }

    /**
     * @param $command
     *
     * @throws ProcessFailedException
     *
     * @return Process
     */
    public function parseSettings()
    {

    }

    public function getCacheBinsStrings() {
      $cache_render  = '$settings["cache"]["bins"]["render"] = "cache.backend.null";';
      $cache_dynamic = '$settings["cache"]["bins"]["dynamic_page_cache"] = "cache.backend.null";';

      return $cache_render . $cache_dynamic;
    }

    public function getSettingsFile($local = null) {
      if ($local) {
        return $this->filesystem->exists($this->site->getRoot() . '/sites/default/local.settings.php')?:
          $this->site->getRoot() . '/sites/default/settings.php';
      }else{
        return $this->site->getRoot() . '/sites/default/settings.php';
      }
    }

    public function getSettingsPath() {
      return $this->site->getRoot() . '/sites/default/';
    }

    public function getExampleSettingsLocalFile() {
        return $this->site->getRoot() . '/sites/example.settings.local.php';
    }

    public function getSettingsLocalFile() {
        return $this->site->getRoot() . '/sites/default/settings.local.php';
    }

    public function getDefaultServicesFile() {
        return $this->site->getRoot() . '/sites/default/default.services.yml';
    }

    public function getServicesFile() {
        return $this->site->getRoot() . '/sites/default/services.yml';
    }

    /**
     * @param $files array
     *
     * @param $perms int
     *
     * @return void
     */
    public function set_perms($files, $perms) {
      foreach ($files as $file) {
        chmod($file, $perms);
      }
    }

    public function exists($file) {
      return $this->exists($file);
    }

    public function write($file, $value) {
      $this->filesystem->dumpFile($file, $value);
    }

    public function copy($source, $dest, $override = true) {
      $this->filesystem->copy($source, $dest, $override);
    }

    public function get_settings_local_str() {
      return
        "
          if (file_exists(__DIR__ . '/settings.local.php')) {
          include __DIR__ . '/settings.local.php';
          }
        ";
    }

    public function get_dev_services_str() {
      return
        "
  services:
    cache.backend.null:
      class: Drupal\Core\Cache\NullBackendFactory";
    }


}
