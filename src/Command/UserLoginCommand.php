<?php

/**
 * @file
 * Contains Drupal\Console\Command\UserLoginCommand.
 */

namespace Drupal\Console\Command;

use Drupal\Component\Utility\SafeMarkup;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Command;
use Drupal\user\Entity\User;

/**
 * Class UserLoginCommand.
 *
 * @package Drupal\Console
 */
class UserLoginCommand extends Command {

  /**
   * @var int Error code: no user can be loaded with the given user id.
   */
  const ERROR_INVALID_USER = 1;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('user:login')
      ->setDescription($this->trans('commands.user.login.description'))
      ->addArgument('user-id', InputArgument::OPTIONAL, $this->trans('commands.user.login.options.user-id'), 1);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    global $base_url;

    $uid = $input->getArgument('user-id');

    $user = User::load($uid);

    if ($user) {
      $url = $base_url . user_pass_reset_url($user);
      $text = $this->trans('command.user.login.messages.url');
      $text = SafeMarkup::format($text, ['@name' => $user->getUsername(), '@url' => $url]);
      $output->writeln($text);
      return 0;
    }

    $text = $this->trans('command.user.login.errors.invalid-user');
    $text = SafeMarkup::format($text, ['@uid' => uid]);
    $output->writeln($text);

    return ERROR_INVALID_USER;
  }
}
