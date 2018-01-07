<?php

namespace Drupal\Console\Override;

class StorageComparer extends \Drupal\Core\Config\StorageComparer
{

    /**
     * {@inheritdoc}
     */
    public function validateSiteUuid()
    {
        return true;
    }
}
