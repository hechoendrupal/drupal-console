<?php
/**
 * @file
 * Contains \Drupal\Console\Helper\CommandDiscoveryHelper.
 */

namespace Drupal\Console\Helper;

use Drupal\Console\Command\CommandDependencies;
use Symfony\Component\Finder\Finder;

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
     * @var CommandDependencies
     */
    protected $commandDependencies;

    /**
     * @var array
     */
    protected $missingDependencies = [];

    /**
     * CommandDiscoveryHelper constructor.
     * @param bool $develop
     */
    public function __construct($develop, CommandDependencies $commandDependencyResolver)
    {
        $this->develop = $develop;
        $this->commandDependencies = $commandDependencyResolver;
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
        $this->loadCustomThemeGenerators();

        return array_merge($consoleCommands, array_merge($customModuleCommands, $customThemeCommands));
    }

    /**
     * @return array
     */
    public function getConsoleCommands()
    {
        $sources = [
            'Console' => [
                'path' => $this->applicationRoot
            ]
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
            $sources = $this->getSite()->getModules(true, true, false, false, true, false);

            if ($this->disabledModules) {
                foreach ($this->disabledModules as $disabledModule) {
                    if (array_key_exists($disabledModule, $sources)) {
                        unset($sources[$disabledModule]);
                    }
                }
            }
        } elseif ($type === 'themes') {
            $sources = $this->getSite()->getThemes(true, true, false, false);
        }

        return $this->discoverCommands($sources);
    }

    /**
     * @return array
     */
    public function loadCustomThemeGenerators()
    {
        $sources = [];

        $sources = $this->getSite()->getThemes(true, true, false, false);

        $this->discoverThemeGenerators($sources);
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
                $sourceType = 'module';
                if (!is_array($source)) {
                    $sourceType = $source->getType();
                }

                $commands = array_merge($commands, $this->extractCommands($directory, $sourceName, $sourceType));
            }
        }

        return $commands;
    }

    /**
     * @param $sources
     */
    private function discoverThemeGenerators($sources)
    {
        foreach ($sources as $sourceName => $source) {
            $directory = sprintf(
                '%s/%s/src/Generator',
                $this->getDrupalHelper()->getRoot(),
                $source->getPath()
            );

            if (is_dir($directory)) {
                $this->extractThemeGenerators($directory, $sourceName);
            }
        }
    }

    /**
     * @param $directory
     * @param $source
     * @param $type
     * @return array
     */
    private function extractCommands($directory, $source, $type)
    {
        $finder = new Finder();
        $finder->files()
            ->name('*Command.php')
            ->in($directory)
            ->depth('< 2');

        $finder->exclude('Exclude');

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

            if ($type!='module') {
                include $file->getPathname();
            }

            $command = $this->validateCommand($className, $source, $type);
            if ($command) {
                $commands[] = $command;
            }
        }

        return $commands;
    }

    /**
     * @param $directory
     * @param $source
     */
    private function extractThemeGenerators($directory, $source)
    {
        $finder = new Finder();
        $finder->files()
            ->name('*Generator.php')
            ->in($directory)
            ->depth('< 2');

        $finder->exclude('Exclude');

        if (!$this->develop) {
            $finder->exclude('Develop');
        }

        foreach ($finder as $file) {
            $className = sprintf(
                'Drupal\%s\Generator\%s',
                $source,
                str_replace(
                    ['/', '.php'], ['\\', ''],
                    $file->getRelativePathname()
                )
            );

            include $file->getPathname();
        }
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
            return false;
        }

        $reflectionClass = new \ReflectionClass($className);

        if ($reflectionClass->isAbstract()) {
            return false;
        }

        if (!$reflectionClass->isSubclassOf('Drupal\\Console\\Command\\Command')) {
            /* TODO remove once Compiler pass is completed */
            if ($type === 'module' && $reflectionClass->isSubclassOf('Symfony\\Component\\Console\\Command\\Command')) {
                $command = $reflectionClass->newInstance();
                if (!$this->getDrupalHelper()->isInstalled()) {
                    $traits = class_uses($command);
                    if (in_array('Drupal\\Console\\Command\\Shared\\ContainerAwareCommandTrait', $traits)) {
                        return false;
                    }
                }

                if (method_exists($command, 'setTranslator')) {
                    $command->setTranslator($this->getTranslator());
                }

                return $command;
            }
            /* TODO remove once Compiler pass is completed */

            return false;
        }

        if (!$this->getDrupalHelper()->isInstalled()
            && $reflectionClass->isSubclassOf('Drupal\\Console\\Command\\ContainerAwareCommand')
        ) {
            return false;
        }

        $dependencies = $this->commandDependencies->read($reflectionClass);

        if ($reflectionClass->getConstructor()->getNumberOfRequiredParameters() > 0) {
            if ($source != 'Console') {
                if ($type === 'module') {
                    $this->getTranslator()->addResourceTranslationsByModule($source);
                } elseif ($type === 'theme') {
                    $this->getTranslator()->addResourceTranslationsByTheme($source);
                }
            }
            $command = $reflectionClass->newInstance($this->getHelperSet());
        } else {
            $command = $reflectionClass->newInstance();
        }

        $this->missingDependencies[$command->getName()] = $dependencies;

        if ($type === 'module') {
            $command->setModule($source);
        } elseif ($type === 'theme') {
            $command->setTheme($source);
        }

        return $command;
    }

    /**
     * @return array
     */
    public function getMissingDependencies()
    {
        return $this->missingDependencies;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'commandDiscovery';
    }
}
