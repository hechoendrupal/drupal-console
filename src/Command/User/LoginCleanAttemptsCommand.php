<?php

/**
 * @file
 * Contains \Drupal\Console\Command\User\LoginCleanAttemptsCommand.
 */

namespace Drupal\Console\Command\User;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;
use Drupal\user\Entity\User;

class LoginCleanAttemptsCommand extends Command
{
    use ContainerAwareCommandTrait;
    use ConfirmationTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->
        setName('user:login:clear:attempts')
            ->setDescription($this->trans('commands.user.login.clear.attempts.description'))
            ->setHelp($this->trans('commands.user.login.clear.attempts.help'))
            ->addArgument('uid', InputArgument::REQUIRED, $this->trans('commands.user.login.clear.attempts.options.user-id'));
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $uid = $input->getArgument('uid');
        // Check if $uid argument is already set.
        if (!$uid) {
            while (true) {
                // Request $uid argument.
                $uid = $io->ask(
                    $this->trans('commands.user.login.clear.attempts.questions.uid'),
                    1,
                    function ($uid) use ($io) {
                        $message = (!is_numeric($uid)) ?
                        $this->trans('commands.user.login.clear.attempts.questions.numeric-uid') :
                        false;
                        // Check if $uid is upper than zero.
                        if (!$message && $uid <= 0) {
                            $message = $this->trans('commands.user.login.clear.attempts.questions.invalid-uid');
                        }
                        // Check if message was defined.
                        if ($message) {
                            $io->error($message);

                            return false;
                        }
                        // Return a valid $uid.
                        return (int) $uid;
                    }
                );

                if ($uid) {
                    break;
                }
            }

            $input->setArgument('uid', $uid);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $uid = $input->getArgument('uid');
        $account = User::load($uid);

        if (!$account) {
            // Error loading User entity.
            $io->error(
                sprintf(
                    $this->trans('commands.user.login.clear.attempts.errors.invalid-user'),
                    $uid
                )
            );

            return 1;
        }

        // Define event name and identifier.
        $event = 'user.failed_login_user';
        // Identifier is created by uid and IP address,
        // Then we defined a generic identifier.
        $identifier = "{$account->id()}-";

        // Retrieve current database connection.
        $database = $this->getDrupalService('database');
        $schema = $database->schema();
        $flood = $schema->findTables('flood');

        if (!$flood) {
            $io->error(
                $this->trans('commands.user.login.clear.attempts.errors.no-flood')
            );

            return 1;
        }

        // Clear login attempts.
        $database->delete('flood')
            ->condition('event', $event)
            ->condition('identifier', $database->escapeLike($identifier) . '%', 'LIKE')
            ->execute();

        // Command executed successful.
        $io->success(
            sprintf(
                $this->trans('commands.user.login.clear.attempts.messages.successful'),
                $uid
            )
        );
    }
}
