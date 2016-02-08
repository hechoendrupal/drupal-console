<?php

namespace Drupal\Console\Utils;

/**
 * Class RequirementChecker
 * @package Drupal\Console\Utils
 */
class RequirementChecker
{
    /**
   * @var array
   */
    protected $requirements = [];

    /**
   * @var array
   */
    protected $checkResult = [];

    public function __construct($config)
    {
        $this->requirements = $config->getFileContents(__DIR__.'/../../requirements.yml');
    }

    private function checkPHPVersion()
    {
        $requiredPHP = $this->requirements['requirements']['php-version']['required'];
        $currentPHP = phpversion();
        $this->checkResult['php-version']['required'] = $requiredPHP;
        $this->checkResult['php-version']['current'] = $currentPHP;
        if (version_compare($currentPHP, $requiredPHP, '<')) {
            $this->checkResult['php-version']['invalid'] = true;
        }
    }

    private function checkRequiredExtensions()
    {
        foreach ($this->requirements['requirements']['extensions']['required'] as $extension) {
            if (!extension_loaded($extension)) {
                $this->checkResult['extensions']['required']['missing'] = $extension;
            }
        }
    }

    private function checkRecommendedExtensions()
    {
        foreach ($this->requirements['requirements']['extensions']['recommended'] as $extension) {
            if (!extension_loaded($extension)) {
                $this->checkResult['extensions']['recommended']['missing'] = $extension;
            }
        }
    }

    private function checkRequiredConfigurations()
    {
        foreach ($this->requirements['requirements']['configurations']['required'] as $configuration) {
            $defaultValue = null;
            if (is_array($configuration)) {
                $defaultValue = current($configuration);
                $configuration = key($configuration);
            }

            if (!ini_get($configuration)) {
                if ($defaultValue) {
                    ini_set($configuration, $defaultValue);
                    $this->checkResult['configurations']['required']['override'] = [
                    $configuration => $defaultValue
                    ];
                    continue;
                }

                $this->checkResult['configurations']['required']['missing'] = $configuration;
            }
        }
    }

    /**
   * @return array
   */
    public function validate()
    {
        $this->checkPHPVersion();
        $this->checkRequiredExtensions();
        $this->checkRecommendedExtensions();
        $this->checkRequiredConfigurations();
        return $this->checkResult;
    }
}
