<?php

/**
 * @file
 * Contains \Drupal\AppConsole\Command\PasswordCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Command\Helper\ConfirmationTrait;

class PasswordResetCommand extends ContainerAwareCommand
{
    use ConfirmationTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('password:reset')
            ->setDescription($this->trans('commands.password.reset.description'))
            ->setHelp($this->trans('commands.password.reset.help'))
            ->addArgument('user', InputArgument::REQUIRED, $this->trans('commands.password.reset.options.user-id'))
            ->addArgument('password', InputArgument::REQUIRED, $this->trans('commands.password.reset.options.password'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user_id = $input->getArgument('user');
        $user = user_load($user_id);

        if (!is_object($user)) {
            $output->writeln(
                '[+] <error>'.sprintf(
                    $this->trans('commands.password.reset.errors.invalid-user'),
                    $user_id
                ).'</error>'
            );

            return;
        }

        $password = $input->getArgument('password');

        //Set password
        try {
            $user->setPassword($password);
            $user->save();
        } catch (\Exception $e) {
            $output->writeln('[+] <error>'.$e->getMessage().'</error>');

            return;
        }

        $output->writeln(
            '[+] <info>'.sprintf(
                $this->trans('commands.password.reset.messages.reset-successful'),
                $user_id
            ).'</info>'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $dialog = $this->getDialogHelper();

        $user = $input->getArgument('user');
        if (!$user) {
            $user = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.password.reset.questions.user'), ''),
                function ($uid) {
                    $uid = (int) $uid;
                    if (is_int($uid) && $uid > 0) {
                        return $uid;
                    } else {
                        throw new \InvalidArgumentException(
                            sprintf($this->trans('commands.password.reset.questions.invalid-uid'), $uid)
                        );
                    }
                },
                false,
                '',
                null
            );
        }
        $input->setArgument('user', $user);

        $password = $input->getArgument('password');
        if (!$password) {
            $password = $dialog->askAndValidate(
                $output,
                $dialog->getQuestion($this->trans('commands.password.hash.questions.password'), ''),
                function ($pass) {
                    if (!empty($pass)) {
                        return $pass;
                    } else {
                        throw new \InvalidArgumentException(
                            sprintf($this->trans('commands.password.hash.questions.invalid-pass'), $pass)
                        );
                    }
                },
                false,
                '',
                null
            );
        }
        $input->setArgument('password', $password);
    }
}
