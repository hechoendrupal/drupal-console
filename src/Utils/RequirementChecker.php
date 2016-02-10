<?php

namespace Drupal\Console\Utils;

use Symfony\Component\Yaml\Parser;

/**
 * Class RequirementChecker
 * @package Drupal\Console\Utils
 */
class RequirementChecker
{
    /**
     * @var Parser
     */
    protected $parser;


    /**
     * @var array
     */
    protected $requirements = [];

    /**
     * @var array
     */
    protected $checkResult = [];

    /**
     * RequirementChecker constructor.
     * @param $parser
     */
    public function __construct($parser)
    {
        $this->parser = $parser;
    }

    private function checkPHPVersion()
    {
        $requiredPHP = $this->requirements['requirements']['php']['required'];
        $currentPHP = phpversion();
        $this->checkResult['php']['required'] = $requiredPHP;
        $this->checkResult['php']['current'] = $currentPHP;
        $this->checkResult['php']['valid'] = version_compare($currentPHP, $requiredPHP, '>=');
    }

    private function checkRequiredExtensions()
    {
        $this->checkResult['extensions']['required']['missing'] = [];
        foreach ($this->requirements['requirements']['extensions']['required'] as $extension) {
            if (!extension_loaded($extension)) {
                $this->checkResult['extensions']['required']['missing'][] = $extension;
            }
        }
    }

    private function checkRecommendedExtensions()
    {
        $this->checkResult['extensions']['recommended']['missing'] = [];
        foreach ($this->requirements['requirements']['extensions']['recommended'] as $extension) {
            if (!extension_loaded($extension)) {
                $this->checkResult['extensions']['recommended']['missing'][] = $extension;
            }
        }
    }

    private function checkRequiredConfigurations()
    {
        $this->checkResult['configurations']['required']['overwritten']  = [];
        $this->checkResult['configurations']['required']['missing']  = [];
        foreach ($this->requirements['requirements']['configurations']['required'] as $configuration) {
            $defaultValue = null;
            if (is_array($configuration)) {
                $defaultValue = current($configuration);
                $configuration = key($configuration);
            }

            if (!ini_get($configuration)) {
                if ($defaultValue) {
                    ini_set($configuration, $defaultValue);
                    $this->checkResult['configurations']['required']['overwritten'] = [
                        $configuration => $defaultValue
                    ];
                    continue;
                }

                $this->checkResult['configurations']['required']['missing'][] = $configuration;
            }
        }
    }

    /**
     * @param $file
     * @return array
     */
    public function validate($file)
    {
        if (file_exists($file)) {
            $this->requirements = $this->parser->parse(file_get_contents($file));
        }

        if (!$this->checkResult) {
            $this->checkPHPVersion();
            $this->checkRequiredExtensions();
            $this->checkRecommendedExtensions();
            $this->checkRequiredConfigurations();
        }

        return $this->checkResult;
    }
}
