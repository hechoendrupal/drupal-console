<?php

/**
 * @file
 * Contains \Drupal\Console\Command\User\PasswordHashCommand.
 */

namespace Drupal\Console\Command\User;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Command\ConfirmationTrait;
use Drupal\Console\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Drupal\Console\Style\DrupalStyle;

class PasswordHashCommand extends ContainerAwareCommand
{
    use ConfirmationTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('user:password:hash')
            ->setDescription($this->trans('commands.user.password.hash.description'))
            ->setHelp($this->trans('commands.user.password.hash.help'))
            ->addArgument('password', InputArgument::IS_ARRAY, $this->trans('commands.user.password.hash.options.password'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $passwords = $input->getArgument('password');

        $passHandler = $this->getPassHandler();

        $table = new Table($output);
        $table->setHeaders(
            [
                $this->trans('commands.user.password.hash.messages.password'),
                $this->trans('commands.user.password.hash.messages.hash'),
            ]
        );

        $table->setStyle('compact');

        foreach ($passwords as $password) {
            $table->addRow(
                [
                    $password,
                    $passHandler->hash($password),
                ]
            );
        }

        $table->render();
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $output = new DrupalStyle($input, $output);

        $passwords = $input->getArgument('password');
        if (!$passwords) {
            $passwords = [];
            while (true) {
                $password = $output->ask(
                    $this->trans('commands.user.password.hash.questions.password'),
                    '',
                    function ($pass) use ($passwords) {
                        if (!empty($pass) || count($passwords) >= 1) {
                            return $pass;
                        } else {
                            throw new \InvalidArgumentException(
                                sprintf($this->trans('commands.user.password.hash.questions.invalid-pass'), $pass)
                            );
                        }
                    }
                );

                if (empty($password)) {
                    break;
                }

                $passwords[] = $password;
            }

            $input->setArgument('password', $passwords);
        }
    }
}
