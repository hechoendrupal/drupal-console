<?php

/**
 * @file
 * Contains \Drupal\Console\Command\User\PasswordHashCommand.
 */

namespace Drupal\Console\Command\User;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Core\Password\PasswordInterface;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Style\DrupalStyle;

class PasswordHashCommand extends Command
{
    use CommandTrait;
    use ConfirmationTrait;

    /**
     * @var PasswordInterface
     */
    protected $password;

    /**
     * PasswordHashCommand constructor.
     * @param PasswordInterface $password
     */
    public function __construct(PasswordInterface $password)
    {
        $this->password = $password;
        parent::__construct();
    }

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
        $io = new DrupalStyle($input, $output);

        $passwords = $input->getArgument('password');

        $tableHeader = [
            $this->trans('commands.user.password.hash.messages.password'),
            $this->trans('commands.user.password.hash.messages.hash'),
        ];

        $tableRows = [];
        foreach ($passwords as $password) {
            $tableRows[] = [
                $password,
                $this->password->hash($password),
            ];
        }

        $io->table($tableHeader, $tableRows, 'compact');
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $passwords = $input->getArgument('password');
        if (!$passwords) {
            $passwords = [];
            while (true) {
                $password = $io->ask(
                    $this->trans('commands.user.password.hash.questions.password'),
                    '',
                    function ($pass) use ($passwords, $io) {
                        if (!empty($pass) || count($passwords) >= 1) {
                            if ($pass == '') {
                                return true;
                            }

                            return $pass;
                        } else {
                            $io->error(
                                sprintf($this->trans('commands.user.password.hash.questions.invalid-pass'), $pass)
                            );

                            return false;
                        }
                    }
                );

                if ($password && !is_string($password)) {
                    break;
                }

                if (is_string($password)) {
                    $passwords[] = $password;
                }
            }

            $input->setArgument('password', $passwords);
        }
    }
}
