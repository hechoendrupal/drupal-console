<?php

/**
 * @file
 * Contains \Drupal\Console\Command\User\PasswordHashCommand.
 */

namespace Drupal\Console\Command\User;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Password\PasswordInterface;

class PasswordHashCommand extends Command
{
    /**
     * @var PasswordInterface
     */
    protected $password;

    /**
     * PasswordHashCommand constructor.
     *
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
            ->addArgument(
                'password',
                InputArgument::IS_ARRAY,
                $this->trans('commands.user.password.hash.options.password')
            )
            ->setAliases(['uph']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $password = $input->getArgument('password');
        if (!$password) {
            $password = $this->getIo()->ask(
                $this->trans('commands.user.password.hash.questions.password')
            );

            $input->setArgument('password', [$password]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
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

        $this->getIo()->table($tableHeader, $tableRows, 'compact');
    }
}
