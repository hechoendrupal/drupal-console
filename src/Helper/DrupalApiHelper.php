<?php

/**
 * @file
 * Contains Drupal\Console\Helper\DrupalApiHelper.
 */

namespace Drupal\Console\Helper;

use Symfony\Component\DomCrawler\Crawler;
use Drupal\Console\Utils\Create\Nodes;
use Drupal\Console\Utils\Create\Comments;
use Drupal\Console\Utils\Create\Terms;
use Drupal\Console\Utils\Create\Vocabularies;
use Drupal\Console\Utils\Create\Users;

/**
 * Class DrupalApiHelper
 * @package Drupal\Console\Helper
 */
class DrupalApiHelper extends Helper
{
    /* @var array */
    protected $bundles = [];

    /* @var array */
    protected $roles = [];

    /* @var array */
    protected $vocabularies = [];

    /**
     * @return \Drupal\Console\Utils\Create\Nodes
     */
    public function getCreateNodes()
    {
        $createNodes = new Nodes(
            $this->getService('entity_type.manager'),
            $this->getService('entity_field.manager'),
            $this->getService('date.formatter'),
            $this->getBundles()
        );

        return $createNodes;
    }

    /**
     * @return \Drupal\Console\Utils\Create\Comments
     */
    public function getCreateComments()
    {
        $createComments = new Comments(
            $this->getService('entity_type.manager'),
            $this->getService('entity_field.manager'),
            $this->getService('date.formatter')
        );

        return $createComments;
    }

    /**
     * @return \Drupal\Console\Utils\Create\Terms
     */
    public function getCreateTerms()
    {
        $createTerms = new Terms(
            $this->getService('entity_type.manager'),
            $this->getService('entity_field.manager'),
            $this->getService('date.formatter'),
            $this->getVocabularies()
        );

        return $createTerms;
    }

    /**
     * @return \Drupal\Console\Utils\Create\Vocabularies
     */
    public function getCreateVocabularies()
    {
        $createVocabularies = new Vocabularies(
            $this->getService('entity_type.manager'),
            $this->getService('entity_field.manager'),
            $this->getService('date.formatter')
        );

        return $createVocabularies;
    }

    /**
     * @return \Drupal\Console\Utils\Create\Users
     */
    public function getCreateUsers()
    {
        $createUsers = new Users(
            $this->getService('entity_type.manager'),
            $this->getService('entity_field.manager'),
            $this->getService('date.formatter'),
            $this->getRoles()
        );

        return $createUsers;
    }

    /**
     * @return array
     */
    public function getBundles()
    {
        if (!$this->bundles) {
            $entityManager = $this->getService('entity_type.manager');
            $nodeTypes = $entityManager->getStorage('node_type')->loadMultiple();

            foreach ($nodeTypes as $nodeType) {
                $this->bundles[$nodeType->id()] = $nodeType->label();
            }
        }

        return $this->bundles;
    }

    /**
     * @param bool|FALSE $reset
     * @param bool|FALSE $authenticated
     * @param bool|FALSE $anonymous
     * @return array
     */
    public function getRoles($reset=false, $authenticated=true, $anonymous=false)
    {
        if ($reset || !$this->roles) {
            $entityManager = $this->getService('entity_type.manager');
            $roles = $entityManager->getStorage('user_role')->loadMultiple();
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
     * @return array
     */
    public function getVocabularies()
    {
        if (!$this->vocabularies) {
            $entityManager = $this->getService('entity_type.manager');
            $vocabularies = $entityManager->getStorage('taxonomy_vocabulary')->loadMultiple();

            foreach ($vocabularies as $vocabulary) {
                $this->vocabularies[$vocabulary->id()] = $vocabulary->label();
            }
        }

        return $this->vocabularies;
    }

    /**
     * @param $serviceId
     * @return mixed
     */
    public function getService($serviceId)
    {
        if (!$this->getContainer()) {
            return null;
        }

        if ($this->getContainer()->has($serviceId)) {
            return $this->getContainer()->get($serviceId);
        }

        return null;
    }

    /**
     * Gets the current container.
     *
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     *   A ContainerInterface instance.
     */
    protected function getContainer()
    {
        if (!$this->getKernelHelper()) {
            return null;
        }

        if (!$this->getKernelHelper()->getKernel()) {
            return null;
        }

        return $this->getKernelHelper()->getKernel()->getContainer();
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

        $projectPageContent = $this->getHttpClientHelper()->getUrlAsString(
            sprintf(
                'https://updates.drupal.org/release-history/%s/8.x',
                $module
            )
        );

        if (!$projectPageContent) {
            throw new \Exception('Invalid path.');
        }

        $releases = [];
        $crawler = new Crawler($projectPageContent);
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

        if ($this->getHttpClientHelper()->downloadFile($releaseFilePath, $destination)) {
            return $destination;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'api';
    }

    /**
     * Gets Drupal releases from Packagist API.
     *
     * @param string $url
     * @param int    $limit
     * @param bool   $unstable
     *
     * @return array
     */
    private function getComposerReleases($url, $limit = 10, $unstable = false)
    {
        if (!$url) {
            return [];
        }

        try {
            $packagistJson = json_decode(
                $this->getHttpClientHelper()->getUrlAsString(
                    $url
                )
            );
        } catch (\Exception $e) {
            return [];
        }

        $versions = array_keys((array)$packagistJson->package->versions);

        // Remove Drupal 7 versions
        $i = 0;
        foreach ($versions as $version) {
            if (0 === strpos($version, "7.") || 0 === strpos($version, "dev-7.")) {
                unset($versions[$i]);
            }
            $i++;
        }

        if (!$unstable) {
            foreach ($versions as $key => $version) {
                if (strpos($version, "-")) {
                    unset($versions[$key]);
                }
            }
        }

        if (is_array($versions)) {
            return array_slice($versions, 0, $limit);
        }

        return [];
    }

    /**
     * Gets Drupal releases from Packagist API.
     *
     * @param int  $limit
     * @param bool $unstable
     *
     * @return array
     */
    public function getPackagistDrupalReleases($limit = 10, $unstable = false)
    {
        return $this->getComposerReleases(
            'https://packagist.org/packages/drupal/drupal.json',
            $limit,
            $unstable
        );
    }

    /**
     * Gets Drupal releases from Packagist API.
     *
     * @param int  $limit
     * @param bool $unstable
     *
     * @return array
     */
    public function getPackagistDrupalComposerReleases($limit = 10, $unstable = true)
    {
        return $this->getComposerReleases(
            'https://packagist.org/packages/drupal-composer/drupal-project.json',
            $limit,
            $unstable
        );
    }

    /**
     * Gets Drupal modules releases from Packagist API.
     *
     * @param string $module
     * @param int    $limit
     * @param bool   $unstable
     *
     * @return array
     */
    public function getPackagistModuleReleases($module, $limit = 10, $unstable = true)
    {
        if (!trim($module)) {
            return [];
        }

        return $this->getComposerReleases(
            sprintf(
                'https://packagist.drupal-composer.org/packages/drupal/%s.json',
                trim($module)
            ),
            $limit,
            $unstable
        );
    }
}
