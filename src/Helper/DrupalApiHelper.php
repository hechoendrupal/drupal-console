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
            $this->getService('entity.manager'),
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
            $this->getService('entity.manager'),
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
            $this->getService('entity.manager'),
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
            $this->getService('entity.manager'),
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
            $this->getService('entity.manager'),
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
            $entityManager = $this->getService('entity.manager');
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
            $entityManager = $this->getService('entity.manager');
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
            $entityManager = $this->getService('entity.manager');
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
     * @return array
     * @throws \Exception
     */
    public function getProjectReleases($module, $limit = 100)
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
        foreach ($crawler->filterXPath('./project/releases/release/version') as $element) {
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
            'http://ftp.drupal.org/files/projects/%s-%s.tar.gz',
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
}
