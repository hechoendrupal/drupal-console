<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\CleanLoginFailedCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Command\Helper\ConfirmationTrait;

class UserLoginCleanAttemptsCommand extends ContainerAwareCommand {

  use ConfirmationTrait;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->
        setName('user:login:clear:attempts')
        ->setDescription($this->trans('commands.user.login.clear.attempts.description'))
        ->setHelp($this->trans('commands.user.login.clear.attempts.help'))
        ->addArgument('uid', InputArgument::REQUIRED, $this->trans('commands.user.login.clear.attempts.options.user-id'));
  }

  /**
   * Verify if given User ID is valid value for question uid.
   *
   * @param int $uid User ID to check.
   * @return int A valid User ID.
   * @throws \Symfony\Component\Process\Exception\InvalidArgumentException
   */
  public function validateQuestionsUid($uid) {
    // Check if $uid is numeric.
    $message = (!is_numeric($uid)) ?
        $this->trans('commands.user.login.clear.attempts.questions.numeric-uid') :
        FALSE;
    // Check if $uid is upper than zero.
    if (!$message && $uid <= 0) {
      $message = $this->trans('commands.user.login.clear.attempts.questions.invalid-uid');
    }
    // Check if message was defined.
    if ($message) {
      throw new \Symfony\Component\Process\Exception\InvalidArgumentException($message);
    }
    // Return a valid $uid.
    return (int) $uid;
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {

    // Check if $uid argument is already set.
    if (!$uid = $input->getArgument('uid')) {

      // Retrieve dialog helper.
      $dialog = $this->getDialogHelper();

      // Request $uid argument.
      $uid = $dialog->askAndValidate(
          $output, $dialog->getQuestion($this->trans('commands.user.login.clear.attempts.questions.uid'), 1), array($this, 'validateQuestionsUid'), FALSE, 1
      );
    }
    // Define $uid argument.
    $input->setArgument('uid', $uid);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $uid = $input->getArgument('uid');
    if ($account = \Drupal\user\Entity\User::load($uid)) {
      // Define event name and identifier.
      $event = 'user.failed_login_user';
      // Identifier is created by uid and IP address,
      // Then we defined a generic identifier.
      $identifier = "{$account->id()}-";

      // Retrieve current database connection.
      $connection = \Drupal::database();
      // Clear login attempts.
      $connection->delete('flood')
          ->condition('event', $event)
          ->condition('identifier', $connection->escapeLike($identifier) . '%', 'LIKE')
          ->execute();

      // Command executed successful.
      $output->writeln('[+] <info>' . sprintf(
              $this->trans('commands.user.login.clear.attempts.messages.successful'), $uid
          ) . '</info>');
    }
    else {
      // Error loading User entity.
      $output->writeln('[+] <error>' . sprintf(
              $this->trans('commands.user.login.clear.attempts.errors.invalid-user'), $uid
          ) . '</error>');
    }
  }

}
