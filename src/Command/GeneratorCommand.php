<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drupal\Console\Command;

use Drupal\Console\Generator\Generator;

abstract class GeneratorCommand extends ContainerAwareCommand
{
    private $generator;

    // only useful for unit tests
    public function setGenerator(Generator $generator)
    {
        $this->generator = $generator;
    }

    abstract protected function createGenerator();

    public function getGenerator()
    {
        if (null === $this->generator) {
            $this->generator = $this->createGenerator();
            $this->getRenderHelper()->setSkeletonDirs($this->getSkeletonDirs());
            $this->getRenderHelper()->setTranslator($this->getTranslator());
            $this->generator->setHelperSet($this->getHelperSet());
        }

        return $this->generator;
    }

    protected function getSkeletonDirs()
    {
        $module = $this->getModule();
        if ($module != 'Console') {
            $skeletonDirs[] = sprintf(
                '%s/templates',
                $this->getSite()->getModulePath($module)
            );
        }

        $skeletonDirs[] = __DIR__.'/../../templates';

        return $skeletonDirs;
    }
}
