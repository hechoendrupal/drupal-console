<?php

/**
 * @file
 * Contains \Drupal\Console\Command\User\LoginCleanAttemptsCommand.
 */

namespace Drupal\Console\Command\User;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\user\Entity\User;

class LoginCleanAttemptsCommand extends UserBase
{
    /**
     * @var Connection
     */
    protected $database;

    /**
     * LoginCleanAttemptsCommand constructor.
     *
     * @param Connection                 $database
     * @param EntityTypeManagerInterface $entityTypeManager
     */
    public function __construct(
        Connection $database,
        EntityTypeManagerInterface $entityTypeManager
    ) {
        $this->database = $database;
        parent::__construct($entityTypeManager);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->
        setName('user:login:clear:attempts')
            ->setDescription($this->trans('commands.user.login.clear.attempts.description'))
            ->setHelp($this->trans('commands.user.login.clear.attempts.help'))
            ->addArgument(
                'user',
                InputArgument::REQUIRED,
                $this->trans('commands.user.login.clear.attempts.arguments.user')
            )
            ->setAliases(['ulca']);
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
                $this->trans('commands.user.login.clear.attempts.questions.user')
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
                    $this->trans('commands.user.login.clear.attempts.errors.invalid-user'),
                    $user
                )
            );

            return 1;
        }

        // Define event name and identifier.
        $event = 'user.failed_login_user';
        // Identifier is created by uid and IP address,
        // Then we defined a generic identifier.
        $identifier = "{$userEntity->id()}-";

        // Retrieve current database connection.
        $schema = $this->database->schema();
        $flood = $schema->findTables('flood');

        if (!$flood) {
            $io->error(
                $this->trans('commands.user.login.clear.attempts.errors.no-flood')
            );

            return 1;
        }

        // Clear login attempts.
        $this->database->delete('flood')
            ->condition('event', $event)
            ->condition('identifier', $this->database->escapeLike($identifier) . '%', 'LIKE')
            ->execute();

        // Command executed successful.
        $io->success(
            sprintf(
                $this->trans('commands.user.login.clear.attempts.messages.successful'),
                $userEntity->getUsername()
            )
        );
    }
}
