<?php

/**
 * @file
 * Contains Drupal\Console\Utils\Site.
 */

namespace Drupal\Console\Utils;

use Drupal\Core\Cache\Cache;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;

/**
 * Class DrupalHelper
 *
 * @package Drupal\Console\Utils
 */
class DrupalApi
{
    protected $appRoot;
    protected $entityTypeManager;

    private $caches = [];
    private $bundles = [];
    private $vocabularies = [];
    private $roles = [];

    /**
     * DebugCommand constructor.
     *
     * @param Client  $httpClient
     */

    protected $httpClient;

    /**
     * ServerCommand constructor.
     *
     * @param $appRoot
     * @param $entityTypeManager
     */
    public function __construct($appRoot, $entityTypeManager, Client $httpClient)
    {
        $this->appRoot = $appRoot;
        $this->entityTypeManager = $entityTypeManager;
        $this->httpClient = $httpClient;
    }

    /**
     * @return string
     */
    public function getDrupalVersion()
    {
        return \Drupal::VERSION;
    }

    /**
     * Auxiliary function to get all available drupal caches.
     *
     * @return \Drupal\Core\Cache\CacheBackendInterface[] The all available drupal caches
     */
    public function getCaches()
    {
        if (!$this->caches) {
            foreach (Cache::getBins() as $name => $bin) {
                $this->caches[$name] = $bin;
            }
        }

        return $this->caches;
    }

    /**
     * Validate if a string is a valid cache.
     *
     * @param string $cache The cache name
     *
     * @return mixed The cache name if valid or FALSE if not valid
     */
    public function isValidCache($cache)
    {
        // Get the valid caches
        $caches = $this->getCaches();
        $cacheKeys = array_keys($caches);
        $cacheKeys[] = 'all';

        if (!in_array($cache, array_values($cacheKeys))) {
            return false;
        }

        return $cache;
    }

    /**
     * @return array
     */
    public function getBundles()
    {
        if (!$this->bundles) {
            $nodeTypes = $this->entityTypeManager->getStorage('node_type')->loadMultiple();

            foreach ($nodeTypes as $nodeType) {
                $this->bundles[$nodeType->id()] = $nodeType->label();
            }
        }

        return $this->bundles;
    }

    /**
     * @return array
     */
    public function getVocabularies()
    {
        if (!$this->vocabularies) {
            $vocabularies = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple();

            foreach ($vocabularies as $vocabulary) {
                $this->vocabularies[$vocabulary->id()] = $vocabulary->label();
            }
        }

        return $this->vocabularies;
    }

    /**
     * @param bool|FALSE $reset
     * @param bool|FALSE $authenticated
     * @param bool|FALSE $anonymous
     *
     * @return array
     */
    public function getRoles($reset = false, $authenticated = false, $anonymous = false)
    {
        if ($reset || !$this->roles) {
            $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
            if (!$authenticated) {
                unset($roles['authenticated']);
            }
            if (!$anonymous) {
                unset($roles['anonymous']);
            }
            foreach ($roles as $role) {
                $this->roles[$role->id()] = $role->label();
            }
        }

        return $this->roles;
    }

    /**
     * @param $module
     * @param $limit
     * @param $stable
     * @return array
     * @throws \Exception
     */
    public function getProjectReleases($module, $limit = 10, $stable = false)
    {
        if (!$module) {
            return [];
        }

        $projectPageResponse = $this->httpClient->getUrlAsString(
            sprintf(
                'https://updates.drupal.org/release-history/%s/8.x',
                $module
            )
        );

        if ($projectPageResponse->getStatusCode() != 200) {
            throw new \Exception('Invalid path.');
        }

        $releases = [];
        $crawler = new Crawler($projectPageResponse->getBody()->getContents());
        $filter = './project/releases/release/version';
        if ($stable) {
            $filter = './project/releases/release[not(version_extra)]/version';
        }

        foreach ($crawler->filterXPath($filter) as $element) {
            $releases[] = $element->nodeValue;
        }

        if (count($releases)>$limit) {
            array_splice($releases, $limit);
        }

        return $releases;
    }

    /**
     * @param $project
     * @param $release
     * @param null    $destination
     * @return null|string
     */
    public function downloadProjectRelease($project, $release, $destination = null)
    {
        if (!$release) {
            $releases = $this->getProjectReleases($project, 1);
            $release = current($releases);
        }

        if (!$destination) {
            $destination = sprintf(
                '%s/%s.tar.gz',
                sys_get_temp_dir(),
                $project
            );
        }

        $releaseFilePath = sprintf(
            'https://ftp.drupal.org/files/projects/%s-%s.tar.gz',
            $project,
            $release
        );

        if ($this->downloadFile($releaseFilePath, $destination)) {
            return $destination;
        }

        return null;
    }

    public function downloadFile($url, $destination)
    {
        $this->httpClient->get($url, ['sink' => $destination]);

        return file_exists($destination);
    }

    /**
     * @Todo: Remove when issue https://www.drupal.org/node/2556025 get resolved
     *
     * Rebuilds all caches even when Drupal itself does not work.
     *
     * @param \Composer\Autoload\ClassLoader            $class_loader
     *   The class loader.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The current request.
     *
     * @see rebuild.php
     */
    public function drupal_rebuild($class_loader, \Symfony\Component\HttpFoundation\Request $request)
    {
        // Remove Drupal's error and exception handlers; they rely on a working
        // service container and other subsystems and will only cause a fatal error
        // that hides the actual error.
        restore_error_handler();
        restore_exception_handler();

        // Force kernel to rebuild php cache.
        \Drupal\Core\PhpStorage\PhpStorageFactory::get('twig')->deleteAll();

        // Bootstrap up to where caches exist and clear them.
        $kernel = new \Drupal\Core\DrupalKernel('prod', $class_loader);
        $kernel->setSitePath(\Drupal\Core\DrupalKernel::findSitePath($request));

        // Invalidate the container.
        $kernel->invalidateContainer();

        // Prepare a NULL request.
        $kernel->prepareLegacyRequest($request);

        foreach (Cache::getBins() as $bin) {
            $bin->deleteAll();
        }

        // Disable recording of cached pages.
        \Drupal::service('page_cache_kill_switch')->trigger();

        drupal_flush_all_caches();

        // Restore Drupal's error and exception handlers.
        // @see \Drupal\Core\DrupalKernel::boot()
        set_error_handler('_drupal_error_handler');
        set_exception_handler('_drupal_exception_handler');
    }
}
