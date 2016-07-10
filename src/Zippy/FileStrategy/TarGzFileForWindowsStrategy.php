<?php

/**
 * @file
 * Contains Drupal\Console\Zippy\Strategy\ProjectDownloadTrait.
 */

namespace Drupal\Console\Zippy\FileStrategy;

use Alchemy\Zippy\FileStrategy\AbstractFileStrategy;

/**
 * Class TarGzFileForWindowsStrategy
 * @package Drupal\Console\Zippy/Strategy
 */
class TarGzFileForWindowsStrategy extends AbstractFileStrategy
{
    /**
     * {@inheritdoc}
     */
    protected function getServiceNames()
    {
        return array(
            'Drupal\\Console\\Zippy\\Adapter\\TarGzGNUTarForWindowsAdapter'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFileExtension()
    {
        return 'tar.gz';
    }
}
