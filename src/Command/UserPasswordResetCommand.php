<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\UserPasswordResetCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\AppConsole\Command\Helper\ConfirmationTrait;

class UserPasswordResetCommand extends ContainerAwareCommand
{
    use ConfirmationTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('user:password:reset')
            ->setDescription($this->trans('commands.user.password.reset.description'))
            ->setHelp($this->trans('commands.user.password.reset.help'))
            ->addArgument('user', InputArgument::REQUIRED, $this->trans('commands.user.password.reset.options.user-id'))
            ->addArgument('password', InputArgument::REQUIRED, $this->trans('commands.user.password.reset.options.password'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $messageHelper = $this->getHelperSet()->get('message');
        $uid = $input->getArgument('user');

        $user = \Drupal\user\Entity\User::load($uid);

        if (!$user) {
            throw new \InvalidArgumentException(
                sprintf(
                    $this->trans('commands.user.password.reset.errors.invalid-user'),
                    $uid
                )
            );
        }

        $password = $input->getArgument('password');
        if (!$password) {
            throw new \InvalidArgumentException(
                sprintf(
                    $this->trans('commands.user.password.reset.errors.empty-password'),
                    $uid
                )
            );
        }

        try {
            $user->setPassword($password);
            $user->save();
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                $e->getMessage().'</error>'
            );
        }

        $messageHelper->addSuccessMessage(
            sprintf(
                $this->trans('commands.user.password.reset.messages.reset-successful'),
                $uid
            )
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
                $dialog->getQuestion($this->trans('commands.user.password.reset.questions.user'), ''),
                function ($uid) {
                    $uid = (int) $uid;
                    if (is_int($uid) && $uid > 0) {
                        return $uid;
                    } else {
                        throw new \InvalidArgumentException(
                            sprintf($this->trans('commands.user.password.reset.questions.invalid-uid'), $uid)
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
                $dialog->getQuestion($this->trans('commands.user.password.hash.questions.password'), ''),
                function ($pass) {
                    if (!empty($pass)) {
                        return $pass;
                    } else {
                        throw new \InvalidArgumentException(
                            sprintf($this->trans('commands.user.password.hash.questions.invalid-pass'), $pass)
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
