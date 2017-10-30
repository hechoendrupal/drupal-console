<?php
/**
 * @file
 * Contains \Drupal\Console\Command\User\PasswordResetCommand.
 */

namespace Drupal\Console\Command\User;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Core\Style\DrupalStyle;

class PasswordResetCommand extends UserBase
{
    /**
     * @var Connection
     */
    protected $database;

    /**
     * @var ChainQueue
     */
    protected $chainQueue;

    /**
     * PasswordHashCommand constructor.
     *
     * @param Connection                 $database
     * @param ChainQueue                 $chainQueue
     * @param EntityTypeManagerInterface $entityTypeManager
     */
    public function __construct(
        Connection $database,
        ChainQueue $chainQueue,
        EntityTypeManagerInterface $entityTypeManager
    ) {
        $this->database = $database;
        $this->chainQueue = $chainQueue;
        parent::__construct($entityTypeManager);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('user:password:reset')
            ->setDescription($this->trans('commands.user.password.reset.description'))
            ->setHelp($this->trans('commands.user.password.reset.help'))
            ->addArgument(
                'user',
                InputArgument::REQUIRED,
                $this->trans('commands.user.password.reset.options.user')
            )
            ->addArgument(
                'password',
                InputArgument::REQUIRED,
                $this->trans('commands.user.password.reset.options.password')
            )
            ->setAliases(['upr']);
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
                $this->trans('commands.user.password.reset.questions.user')
            );

            $input->setArgument('user', $user);
        }

        $password = $input->getArgument('password');
        if (!$password) {
            $password = $io->ask(
                $this->trans('commands.user.password.hash.questions.password')
            );

            $input->setArgument('password', $password);
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
                    $this->trans('commands.user.password.reset.errors.invalid-user'),
                    $user
                )
            );

            return 1;
        }

        $password = $input->getArgument('password');
        if (!$password) {
            $io->error(
                sprintf(
                    $this->trans('commands.user.password.reset.errors.empty-password'),
                    $password
                )
            );

            return 1;
        }

        try {
            $userEntity->setPassword($password);
            $userEntity->save();

            $schema = $this->database->schema();
            $flood = $schema->findTables('flood');

            if ($flood) {
                $this->chainQueue
                    ->addCommand('user:login:clear:attempts', ['user' => $user]);
            }

            $io->success(
                sprintf(
                    $this->trans('commands.user.password.reset.messages.reset-successful'),
                    $user
                )
            );
            return 0;
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return 1;
        }
    }
}
