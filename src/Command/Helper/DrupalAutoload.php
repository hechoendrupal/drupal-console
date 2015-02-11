<?php

namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Finder\Finder;

class DrupalAutoload extends Helper
{
    /**
     * @var Finder
     */
    protected $finder;

    /**
     * @param Finder $finder
     */
    public function __construct(Finder $finder)
    {
        $this->finder = $finder;
    }

    /**
     * @return null | string
     */
    public function findAutoload()
    {
        $currentPath = getcwd() . '/';
        $relativePath = '';
        $autoloadFound = 0;
        $iterator = false;

        while ($autoloadFound === 0) {
            $path = $currentPath . $relativePath . 'core/vendor';

            try {
                $iterator = $this->finder
                    ->files()
                    ->name('autoload.php')
                    ->in($path)
                    ->depth('< 1');
                $autoloadFound = $iterator->count();
            } catch (\InvalidArgumentException $e) {
                $relativePath .= '../';

                if (realpath($currentPath . $relativePath) === '/') {
                    break;
                }
            }
        }

        if ($iterator) {
            foreach ($iterator as $file) {
                $bootstrapRealPath = $file->getRealpath();
                break;
            }
            return $bootstrapRealPath;
        }
        else {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'drupal-autoload';
    }

}