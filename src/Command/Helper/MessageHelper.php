<?php
/**
 * @file
 * Contains Drupal\AppConsole\Command\Helper\MessageHelper.
 */

namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\Helper;

class MessageHelper extends Helper
{

    const MESSAGE_ERROR = 'error';
    const MESSAGE_WARNING = 'warning';
    const MESSAGE_INFO = 'info';
    const MESSAGE_SUCCESS = 'success';

    protected $messages = [];

    public function showMessages($output, $type = null)
    {
        if ($type) {
            $messages =  $this->messages[$type];
            $this->showMessagesByType($output, $messages, $type);
        }

        $messages = $this->messages[self::MESSAGE_ERROR];
        $this->showMessagesByType($output, $messages, self::MESSAGE_ERROR);

        $messages = $this->messages[self::MESSAGE_WARNING];
        $this->showMessagesByType($output, $messages, self::MESSAGE_WARNING);

        $messages = $this->messages[self::MESSAGE_INFO];
        $this->showMessagesByType($output, $messages, self::MESSAGE_INFO);

        $messages = $this->messages[self::MESSAGE_SUCCESS];
        $this->showMessagesByType($output, $messages, self::MESSAGE_SUCCESS);
    }

    private function showMessagesByType($output, $messages, $type)
    {
        if ($messages) {
            foreach ($messages as $message) {
                $this->showMessage($output, $message, $type);
            }
        }
    }

    public function showMessage($output, $message, $type = self::MESSAGE_INFO)
    {
        if ($type == self::MESSAGE_ERROR) {
            $style = 'bg=red;fg=white';
        }
        if ($type == self::MESSAGE_WARNING) {
            $style = 'bg=magenta;fg=white';
        }
        if ($type == self::MESSAGE_INFO) {
            $style = 'bg=blue;fg=white';
        }
        if ($type == self::MESSAGE_SUCCESS) {
            $style = 'bg=green;fg=white';
        }
        $output->writeln([
          '',
          $this->getHelperSet()->get('formatter')->formatBlock(
            $message,
            $style,
            false
          ),
          '',
        ]);
    }

    private function addMessage($message, $type)
    {
        $this->messages[$type][] = $message;
    }

    public function addErrorMessage($message)
    {
        $this->addMessage($message, self::MESSAGE_ERROR);
    }

    public function addWarningMessage($message)
    {
        $this->addMessage($message, self::MESSAGE_WARNING);
    }

    public function addInfoMessage($message)
    {
        $this->addMessage($message, self::MESSAGE_INFO);
    }

    public function addSuccessMessage($message)
    {
        $this->addMessage($message, self::MESSAGE_SUCCESS);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'message';
    }
}
