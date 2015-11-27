<?php
/**
 * @file
 * Contains \Drupal\Console\Helper\CommandDiscoveryHelper.
 */

namespace Drupal\Console\Helper;

use Symfony\Component\Finder\Finder;
use Drupal\Console\Helper\Helper;

/**
 * Class CommandDiscovery
 * @package Drupal\Console\Utils
 */
class CommandDiscoveryHelper extends Helper
{
    /**
     * @var string
     */
    protected $applicationRoot = '';

    /**
     * @var array
     */
    protected $disabledModules = [];

    /**
     * @var bool
     */
    protected $develop = false;

    /**
     * CommandDiscoveryHelper constructor.
     * @param bool $develop
     */
    public function __construct($develop)
    {
        $this->develop = $develop;
    }

    /**
     * @param string $applicationRoot
     */
    public function setApplicationRoot($applicationRoot)
    {
        $this->applicationRoot = $applicationRoot;
    }

    /**
     * @param array $disabledModules
     */
    public function setDisabledModules($disabledModules)
    {
        $this->disabledModules = $disabledModules;
    }

    /**
     * @return array
     */
    public function getCommands()
    {
        $consoleCommands = $this->getConsoleCommands();
        $customModuleCommands = $this->getCustomCommands();
        $customThemeCommands = $this->getCustomCommands('themes');

        return array_merge($consoleCommands, array_merge($customModuleCommands, $customThemeCommands));
    }

    /**
     * @return array
     */
    public function getConsoleCommands()
    {
        $sources = ['Console' => [
            'path' => $this->applicationRoot]
        ];

        return $this->discoverCommands($sources);
    }

    /**
     * @return array
     */
    public function getCustomCommands($type = 'modules')
    {
        $sources = [];

        if ($type === 'modules') {
          $sources = $this->getSite()->getModules(true, false, false, true, false);

            if ($this->disabledModules) {
              foreach ($this->disabledModules as $disabledModule) {
                if (array_key_exists($disabledModule, $sources)) {
                  unset($sources[$disabledModule]);
                }
              }
            }
        }
        else if ($type === 'themes') {
            $sources = $this->getSite()->getThemes(true, false, false);
        }

        return $this->discoverCommands($sources);
    }

    /**
     * @param $sources
     * @return array
     */
    private function discoverCommands($sources)
    {
        $commands = [];
        foreach ($sources as $sourceName => $source) {
            if ($sourceName === 'Console') {
                $directory = sprintf(
                    '%s/src/Command',
                    $source['path']
                );
            } else {
                $directory = sprintf(
                    '%s/%s/src/Command',
                    $this->getDrupalHelper()->getRoot(),
                    $source->getPath()
                );
            }

            if (is_dir($directory)) {
                if (strpos($directory, 'modules') !== 'false') {
                  $commands = array_merge($commands, $this->extractCommands($directory, $sourceName));
                }
                else if (strpos($directory, 'themes') !== 'false') {
                  $commands = array_merge($commands, $this->extractCommands($directory, $sourceName, 'theme'));
                }
            }
        }

        return $commands;
    }

    /**
     * @param $directory
     * @param $source
     * @param $type
     * @return array
     */
    private function extractCommands($directory, $source, $type = 'module')
    {
        $finder = new Finder();
        $finder->files()
            ->name('*Command.php')
            ->in($directory)
            ->depth('< 2');

        $finder->exclude('Autowire');

        if (!$this->develop) {
            $finder->exclude('Develop');
        }

        $commands = [];

        foreach ($finder as $file) {
            $className = sprintf(
                'Drupal\%s\Command\%s',
                $source,
                str_replace(
                    ['/', '.php'], ['\\', ''],
                    $file->getRelativePathname()
                )
            );
            $command = $this->validateCommand($className, $source, $type);
            if ($command) {
                $commands[] = $command;
            }
        }

        return $commands;
    }

    /**
     * @param $className
     * @param $source
     * @param $type
     * @return mixed
     */
    private function validateCommand($className, $source, $type)
    {
        if (!class_exists($className)) {
            return;
        }

        $reflectionClass = new \ReflectionClass($className);

        if ($reflectionClass->isAbstract()) {
            return;
        }

        if (!$reflectionClass->isSubclassOf('Drupal\\Console\\Command\\Command')) {
            return;
        }

        if (!$this->getDrupalHelper()->isInstalled() && $reflectionClass->isSubclassOf('Drupal\\Console\\Command\\ContainerAwareCommand')) {
            return;
        }

        if ($reflectionClass->getConstructor()->getNumberOfRequiredParameters() > 0) {
            if ($source != 'Console' && $type === 'module') {
                if ($type === 'module') {
                  $this->getTranslator()->addResourceTranslationsByModule($source);
                }
                else if ($type === 'theme') {
                  $this->getTranslator()->addResourceTranslationsByTheme($source);
                }
            }
            $command = $reflectionClass->newInstance($this->getHelperSet());
        } else {
            $command = $reflectionClass->newInstance();
        }

        if ($type === 'module') {
          $command->setModule($source);
        }
        else if ($type === 'theme') {
          $command->setTheme($source);
        }

        return $command;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'commandDiscovery';
    }
}
