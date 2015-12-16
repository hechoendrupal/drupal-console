<?php

/**
 * @file
 * Contains Drupal\Console\Command\MessageHelper.
 */

namespace Drupal\Console\Helper;

use \Psr\Log\LoggerInterface;
use \Psr\Log\LoggerTrait;
use \Psr\Log\LogLevel;
use Drupal\Console\Helper\Helper;
use Drupal\Console\Style\DrupalStyle;

class MessageHelper extends Helper implements LoggerInterface
{
    use LoggerTrait;

    /**
     * @var string
     */
    const MESSAGE_ERROR = LogLevel::ERROR;
    /**
     * @var string
     */
    const MESSAGE_WARNING = LogLevel::WARNING;
    /**
     * @var string
     */
    const MESSAGE_INFO = LogLevel::INFO;
    /**
     * @var string
     */
    const MESSAGE_SUCCESS = 'success';
    /**
     * @var string
     */
    const MESSAGE_DEFAULT = LogLevel::NOTICE;

    /**
     * @var array
     */
    protected $types = [
        self::MESSAGE_ERROR,
        self::MESSAGE_WARNING,
        self::MESSAGE_INFO,
        self::MESSAGE_SUCCESS,
        self::MESSAGE_DEFAULT,
    ];

    /**
     * @var array
     */
    protected $messages = [];

    /**
     * @param $output
     * @param string $type
     */
    public function showMessages($output, $type = null)
    {
        if ($type) {
            $messages = $this->messages[$type];
            $this->showMessagesByType($output, $messages, $type);
        }

        foreach ($this->types as $messageType) {
            if (isset($this->messages[$messageType])) {
                $messages = $this->messages[$messageType];
                $this->showMessagesByType($output, $messages, $messageType);
            }
        }
    }

    /**
     * @param $output
     * @param array  $messages
     * @param string $type
     */
    private function showMessagesByType($output, $messages, $type)
    {
        if ($messages) {
            foreach ($messages as $message) {
                $this->showMessage($output, $message, $type);
            }
        }
    }

    /**
     * @param $output
     * @param array  $message
     * @param string $type
     */
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
        if ($type == self::MESSAGE_DEFAULT) {
            $style = 'fg=green';
        }

        $outputMessage = [
            '',
            $this->getFormatterHelper()->formatBlock(
                $message,
                $style,
                false
            ),
            '',
        ];

        if ($type == self::MESSAGE_DEFAULT) {
            unset($outputMessage[2]);
            unset($outputMessage[0]);
        }

        $output->writeln($outputMessage);
    }

    /**
     *  @inheritdoc
     */
    public function log($level, $message, array $context = array())
    {
        $this->addMessage($message, $type);
    }

    /**
     * @param string $message
     * @param string $type
     */
    private function addMessage($message, $type)
    {
        $this->messages[$type][] = $message;
    }

    /**
     * @param string $message
     */
    public function addDefaultMessage($message)
    {
        $this->addMessage($message, self::MESSAGE_DEFAULT);
    }

    /**
     * @param string $message
     */
    public function addErrorMessage($message)
    {
        $this->addMessage($message, self::MESSAGE_ERROR);
    }

    /**
     * @param string $message
     */
    public function addWarningMessage($message)
    {
        $this->addMessage($message, self::MESSAGE_WARNING);
    }

    /**
     * @param string $message
     */
    public function addInfoMessage($message)
    {
        $this->addMessage($message, self::MESSAGE_INFO);
    }

    /**
     * @param string $message
     */
    public function addSuccessMessage($message)
    {
        $this->addMessage($message, self::MESSAGE_SUCCESS);
    }

    /**
     * @param $output
     * @param string $files
     */
    public function showGeneratedFiles($output, $files)
    {
        $this->showFiles(
            $output,
            $files,
            'application.console.messages.files.generated',
            'application.site.messages.path',
            $this->getDrupalHelper()->getRoot()
        );
    }

    /**
     * @param $output
     * @param string $files
     */
    public function showCopiedFiles($output, $files)
    {
        $this->showFiles(
            $output,
            $files,
            'application.console.messages.files.copied',
            'application.user.messages.path',
            rtrim(getenv('HOME') ?: getenv('USERPROFILE'), '/\\').'/.console/'
        );
    }

    /**
     * @param $output
     * @param string $files
     * @param string $headerKey
     * @param string $pathKey
     * @param string $path+
     */
    private function showFiles($output, $files, $headerKey, $pathKey, $path)
    {
        if (!$files) {
            return;
        }

        $output->writeln($this->getTranslator()->trans($headerKey));

        $output->writeln(
            sprintf(
                '<info>%s:</info> <comment>%s</comment>',
                $this->getTranslator()->trans($pathKey),
                $path
            )
        );

        $index = 1;
        foreach ($files as $file) {
            $this->showFile($output, $file, $index);
            ++$index;
        }
    }

    /**
     * @param $output
     * @param string $file
     * @param int    $index
     */
    private function showFile($output, $file, $index)
    {
        $output->writeln(
            sprintf(
                '<info>%s</info> - <comment>%s</comment>',
                $index,
                $file
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'message';
    }
}
