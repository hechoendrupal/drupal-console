<?php

/**
 * @file
 * Contains Drupal\Console\Utils\Site.
 */

namespace Drupal\Console\Utils;

use Drupal\Core\Cache\Cache;

/**
 * Class DrupalHelper
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
     * ServerCommand constructor.
     * @param $appRoot
     * @param $entityTypeManager
     */
    public function __construct($appRoot, $entityTypeManager)
    {
        $this->appRoot = $appRoot;
        $this->entityTypeManager = $entityTypeManager;
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
     * @return array The all available drupal caches
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
    public function getRoles($reset=false, $authenticated=true, $anonymous=false)
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
}
