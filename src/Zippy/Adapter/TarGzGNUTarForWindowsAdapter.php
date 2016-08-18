<?php

/**
 * @file
 * Contains Drupal\Console\Zippy\Adapter\TarGzGNUTarForWindowsAdapter.
 */

namespace Drupal\Console\Zippy\Adapter;

use Alchemy\Zippy\Adapter\GNUTar\TarGzGNUTarAdapter;
use Alchemy\Zippy\Adapter\Resource\ResourceInterface;
use Alchemy\Zippy\Exception\NotSupportedException;

/**
 * Class TarGzGNUTarForWindowsAdapter
 * @package Drupal\Console\Zippy\Adapter
 */
class TarGzGNUTarForWindowsAdapter extends TarGzGNUTarAdapter
{
    /**
     * @inheritdoc
     */
    protected function doAdd(ResourceInterface $resource, $files, $recursive)
    {
        throw new NotSupportedException('Updating a compressed tar archive is not supported.');
    }

    /**
     * @inheritdoc
     */
    protected function getLocalOptions()
    {
        return array_merge(parent::getLocalOptions(), array('--force-local'));
    }
}
