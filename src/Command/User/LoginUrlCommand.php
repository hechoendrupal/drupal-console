<?php

/**
 * @file
 * Contains Drupal\Console\Command\User\LoginUrlCommand.
 */

namespace Drupal\Console\Command\User;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class UserLoginCommand.
 *
 * @package Drupal\Console
 */
class LoginUrlCommand extends UserBase
{
    /**
     * LoginUrlCommand constructor.
     *
     * @param EntityTypeManagerInterface $entityTypeManager
     */
    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        parent::__construct($entityTypeManager);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('user:login:url')
            ->setDescription($this->trans('commands.user.login.url.description'))
            ->addArgument(
                'user',
                InputArgument::REQUIRED,
                $this->trans('commands.user.login.url.options.user'),
                null
            )
            ->setAliases(['ulu']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $user = $input->getArgument('user');
        if (!$user) {
            $user = $io->ask(
                $this->trans('commands.user.login.url.questions.user')
            );

            $input->setArgument('user', $user);
        }
    }

    /**
   * {@inheritdoc}
   */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $user = $input->getArgument('user');
        $userEntity = $this->getUserEntity($user);

        if (!$userEntity) {
            $io->error(
                sprintf(
                    $this->trans('commands.user.login.url.errors.invalid-user'),
                    $user
                )
            );

            return 1;
        }

        $url = user_pass_reset_url($userEntity) . '/login';
        $io->success(
            sprintf(
                $this->trans('commands.user.login.url.messages.url'),
                $userEntity->getUsername()
            )
        );

        $io->simple($url);
        $io->newLine();

        return 0;
    }
}
