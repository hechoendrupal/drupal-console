<?php

/**
 * @file
 * Contains Drupal\Console\Command\User\LoginUrlCommand.
 */

namespace Drupal\Console\Command\User;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ContainerAwareCommandTrait;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class UserLoginCommand.
 *
 * @package Drupal\Console
 */
class LoginUrlCommand extends Command
{
    use ContainerAwareCommandTrait;

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
        $io = new DrupalStyle($input, $output);

        $uid = $input->getArgument('user-id');
        $user = $this->getDrupalService('entity_type.manager')->getStorage('user')->load($uid);

        if (!$user) {
            $io->error(
                sprintf(
                    $this->trans('commands.user.login.url.errors.invalid-user'),
                    $uid
                )
            );

            return 1;
        }

        $url = user_pass_reset_url($user);
        $io->success(
            sprintf(
                $this->trans('commands.user.login.url.messages.url'),
                $user->getUsername(),
                $url
            )
        );
    }
}
