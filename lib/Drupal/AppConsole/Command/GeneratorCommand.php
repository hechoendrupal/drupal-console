<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Drupal\AppConsole\Command\ContainerAwareCommand;
use Drupal\AppConsole\Command\Helper\DialogHelper;
use Drupal\AppConsole\Command\Generator;

/**
 * Base class for generator commands.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
abstract class GeneratorCommand extends ContainerAwareCommand
{
    private $generator;

    // only useful for unit tests
    public function setGenerator(Generator $generator)
    {
        $this->generator = $generator;
    }

    protected abstract function createGenerator();

    protected function getGenerator()
    {
        if (null === $this->generator) {
            $this->generator = $this->createGenerator();
            $this->generator->setSkeletonDirs($this->getSkeletonDirs($bundle));
        }

        return $this->generator;
    }

    protected function getSkeletonDirs()
    {
        $skeletonDirs = array();
        $skeletonDirs[] = __DIR__.'/../Resources/skeleton';
        $skeletonDirs[] = __DIR__.'/../Resources';
        return $skeletonDirs;
    }

    protected function getDialogHelper()
    {
        $dialog = $this->getHelperSet()->get('dialog');
        if (!$dialog || get_class($dialog) !== 'Drupal\AppConsole\Command\Helper\DialogHelper') {
            $this->getHelperSet()->set(new DialogHelper(), 'dialog');
        }

        return $dialog;
    }
}
