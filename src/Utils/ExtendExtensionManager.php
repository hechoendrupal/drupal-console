<?php

namespace Drupal\Console\Utils;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\Finder;

class ExtendExtensionManager
{
    /**
     * @var array
     */
    protected $configData = [];

    /**
     * @var array
     */
    protected $servicesData = [];

    /**
     * @var array
     */
    protected $configFiles;

    /**
     * @var array
     */
    protected $servicesFiles;

    /**
     * @var boolean
     */
    protected $processed;

    /**
     * ExtendExtensionManager constructor.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * @param string $composerFile
     *
     * @return bool
     */
    public function isValidPackageType($composerFile)
    {
        if (!is_file($composerFile)) {
            return false;
        }

        $composerContent = json_decode(file_get_contents($composerFile), true);
        if (!$composerContent) {
            return false;
        }

        if (!array_key_exists('type', $composerContent)) {
            return false;
        }

        return $composerContent['type'] === 'drupal-console-library';
    }

    /**
     * @param string $configFile
     */
    public function addConfigFile($configFile)
    {
        $configData = $this->parseData($configFile);
        if ($this->isValidConfigData($configData)) {
            $this->configFiles[] = $configFile;
            $this->configData = array_merge_recursive(
                $configData,
                $this->configData
            );
        }
    }

    /**
     * @param string $servicesFile
     */
    public function addServicesFile($servicesFile)
    {
        $servicesData = $this->parseData($servicesFile);
        if ($this->isValidServicesData($servicesData)) {
            $this->servicesFiles[] = $servicesFile;
            $this->servicesData = array_merge_recursive(
                $servicesData,
                $this->servicesData
            );
        }
    }

    /**
     * init
     */
    private function init()
    {
        $this->configData = [];
        $this->servicesData = [];
        $this->configFiles = [];
        $this->servicesFiles = [];
        $this->processed = false;
    }

    /**
     * @param $file
     * @return array|mixed
     */
    private function parseData($file)
    {
        if (!file_exists($file)) {
            return [];
        }

        $data = Yaml::parse(
            file_get_contents($file)
        );

        if (!$data) {
            return [];
        }

        return $data;
    }

    public function processProjectPackages($directory)
    {
        $finder = new Finder();
        $finder->files()
            ->name('composer.json')
            ->contains('drupal-console-library')
            ->in($directory);

        foreach ($finder as $file) {
            $this->processComposerFile($file->getPathName());
        }

        $this->processed = true;
    }

    /**
     * @param $composerFile
     */
    private function processComposerFile($composerFile)
    {
        $packageDirectory = dirname($composerFile);

        $configFile = $packageDirectory.'/console.config.yml';
        $this->addConfigFile($configFile);

        $servicesFile = $packageDirectory.'/console.services.yml';
        $this->addServicesFile($servicesFile);
    }

    /**
     * @param array $configData
     *
     * @return boolean
     */
    private function isValidConfigData($configData)
    {
        if (!$configData) {
            return false;
        }

        if (!array_key_exists('application', $configData)) {
            return false;
        }

        if (!array_key_exists('autowire', $configData['application'])) {
            return false;
        }

        if (!array_key_exists('commands', $configData['application']['autowire'])) {
            return false;
        }

        return true;
    }

    /**
     * @param array $servicesData
     *
     * @return boolean
     */
    private function isValidServicesData($servicesData)
    {
        if (!$servicesData) {
            return false;
        }

        if (!array_key_exists('services', $servicesData)) {
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    public function getConfigData()
    {
        return $this->configData;
    }

    /**
     * @return array
     */
    public function getServicesData()
    {
        return $this->servicesData;
    }

    /**
     * @return array
     */
    public function getConfigFiles()
    {
        return $this->configFiles;
    }

    /**
     * @return array
     */
    public function getServicesFiles()
    {
        return $this->servicesFiles;
    }

    /**
     * @return bool
     */
    public function isProcessed()
    {
        return $this->processed;
    }
}
