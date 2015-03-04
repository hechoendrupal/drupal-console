<?php
/**
 * @file
 * Contains Drupal\AppConsole\Command\Helper\MessageHelper.
 */

namespace Drupal\AppConsole\Command\Helper;

use Symfony\Component\Console\Helper\Helper;
use Drupal\AppConsole\Command\Helper\TranslatorHelper;

class MessageHelper extends Helper
{
    /**
     * @param TranslatorHelper $translator
     */
    function __construct(TranslatorHelper $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @var TranslatorHelper
     */
    protected $translator;

    /**
     * @var string
     */
    const MESSAGE_ERROR = 'error';
    /**
     * @var string
     */
    const MESSAGE_WARNING = 'warning';
    /**
     * @var string
     */
    const MESSAGE_INFO = 'info';
    /**
     * @var  string
     */
    const MESSAGE_SUCCESS = 'success';

    /**
     * @var array
     */
    protected $types = [
        self::MESSAGE_ERROR,
        self::MESSAGE_WARNING,
        self::MESSAGE_INFO,
        self::MESSAGE_SUCCESS
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
     * @param array     $messages
     * @param string    $type
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
     * @param array     $message
     * @param string    $type
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
        if ($files) {
            $this->showMessage(
              $output,
              $this->translator->trans('application.console.messages.generated.files')
            );

            $output->writeln(sprintf(
              '<info>%s:</info><comment>%s</comment>',
              $this->translator->trans('application.site.messages.path'),
              DRUPAL_ROOT
            ));

            $index = 1;
            foreach ($files as $file) {
                $this->showFile($output, $file, $index);
                $index++;
            }
        }
    }

    /**
     * @param $output
     * @param string $file
     * @param int    $index
     */
    private function showFile($output, $file, $index){
        $output->writeln(sprintf(
          '<info>%s</info> - <comment>%s</comment>',
          $index,
          $file
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'message';
    }
}
