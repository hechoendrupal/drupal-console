<?php

/**
 * @file
 * Contains \Drupal\Console\Helper\RemoteHelper.
 */

namespace Drupal\Console\Helper;

use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;
use Drupal\Console\Helper\Helper;

/**
 * Class RemoteHelper
 * @package \Drupal\Console\Helper\RemoteHelper
 */
class RemoteHelper extends Helper
{
    /**
   * @param string $commandName
   * @param string $target
   * @param array  $targetConfig
   * @param array  $inputCommand
   * @param array  $userHomeDir
   * @return string
   */
    public function executeCommand(
        $commandName,
        $target,
        $targetConfig,
        $inputCommand,
        $userHomeDir
    ) {
        $remoteCommand = str_replace(
            [sprintf('\'%s\'', $commandName), sprintf('target=\'%s\'', $target)],
            [$commandName, sprintf('root=%s', $targetConfig['root'])],
            $inputCommand
        );

        $remoteCommand = sprintf(
            '%s %s',
            $targetConfig['console'],
            $remoteCommand
        );

        $key = new RSA();
        if (array_key_exists('passphrase', $targetConfig['keys'])) {
            $passphrase = $targetConfig['keys']['passphrase'];
            $passphrase = realpath(preg_replace('/~/', $userHomeDir, $passphrase, 1));
            $key->setPassword(trim(file_get_contents($passphrase)));
        }
        $private = $targetConfig['keys']['private'];
        $private = realpath(preg_replace('/~/', $userHomeDir, $private, 1));

        if (!$key->loadKey(trim(file_get_contents($private)))) {
            return $this->getTranslator()->trans('commands.site.debug.messages.private-key');
        }

        $ssh = new SSH2($targetConfig['host']);
        if (!$ssh->login('root', $key)) {
            return $this->getTranslator()->trans('commands.site.debug.messages.error-connect');
        } else {
            return $ssh->exec($remoteCommand);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'remote';
    }
}
