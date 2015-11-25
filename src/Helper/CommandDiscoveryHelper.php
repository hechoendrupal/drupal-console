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
        $customCommands = $this->getCustomCommands();
        $customThemeCommands = $this->getCustomThemeCommands();

        return array_merge($consoleCommands, $customCommands, );
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
    public function getCustomCommands()
    {
        $sources = $this->getSite()->getModules(true, false, false, true, false);
        $sources = $this->getSite()->getThemes(true, false, false);

        if ($this->disabledModules) {
            foreach ($this->disabledModules as $disabledModule) {
                if (array_key_exists($disabledModule, $sources)) {
                    unset($sources[$disabledModule]);
                }
            }
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
                $commands = array_merge($commands, $this->extractCommands($directory, $sourceName));
            }
        }

        return $commands;
    }

    /**
     * @param $directory
     * @param $source
     * @return array
     */
    private function extractCommands($directory, $source)
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
            $command = $this->validateCommand($className, $source);
            if ($command) {
                $commands[] = $command;
            }
        }

        return $commands;
    }

    /**
     * @param $className
     * @param $source
     * @return mixed
     */
    private function validateCommand($className, $source)
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
            if ($source != 'Console') {
                $this->getTranslator()->addResourceTranslationsBySource($source);
            }
            $command = $reflectionClass->newInstance($this->getHelperSet());
        } else {
            $command = $reflectionClass->newInstance();
        }

        $command->setSource($source);

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
