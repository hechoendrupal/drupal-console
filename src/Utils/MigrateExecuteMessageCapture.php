<?php

/**
 * @file
 * Contains \Drupal\Console\Utils\MigrateExecuteMessageCapture.
 */

namespace Drupal\Console\Utils;

use Drupal\migrate\MigrateMessageInterface;

/**
 * Defines a migrate message class.
 */
class MigrateExecuteMessageCapture implements MigrateMessageInterface
{
    /**
     * Array of recorded messages.
     *
     * @var array
     */
    protected $messages = array();

    /**
     * {@inheritdoc}
     */
    public function display($message, $type = 'status')
    {
        $this->messages[] = $message;
    }

    /**
     * Clear out any captured messages.
     */
    public function clear()
    {
        $this->messages = array();
    }

    /**
     * Return any captured messages.
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }
}
