<?php

/**
 * @file
 * Contains Drupal\Console\Command\User\LoginUrlCommand.
 */

namespace Drupal\Console\Command\User;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Console\Command\ContainerAwareCommand;

/**
 * Class UserLoginCommand.
 *
 * @package Drupal\Console
 */
class LoginUrlCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('user:login:url')
            ->setDescription($this->trans('commands.user.login.url.description'))
            ->addArgument(
                'user-id',
                InputArgument::REQUIRED,
                $this->trans('commands.user.login.url.options.user-id'),
                null
            );
    }

    /**
   * {@inheritdoc}
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $uid = $input->getArgument('user-id');
        $user = $this->getEntityManager()->getStorage('user')->load($uid);
        $message = $this->getMessageHelper();

        if (!$user) {
            $text = $this->trans('commands.user.login.url.errors.invalid-user');
            $text = SafeMarkup::format($text, [':uid' => $uid]);
            $message->addErrorMessage($text);
            return;
        }

        $url = user_pass_reset_url($user);
        $text = $this->trans('commands.user.login.url.messages.url');
        $text = SafeMarkup::format($text, ['@name' => $user->getUsername(), '@url' => $url]);
        $message->addSuccessMessage($text);
    }
}
