<?php
/**
 * @file
 * Contains \Drupal\Console\Command\User\PasswordResetCommand.
 */

namespace Drupal\Console\Command\User;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Core\Database\Connection;
use Drupal\Console\Core\Utils\ChainQueue;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\user\Entity\User;
use Drupal\Console\Core\Style\DrupalStyle;

class PasswordResetCommand extends Command
{
    use CommandTrait;
    use ConfirmationTrait;

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
     * @param Connection $database
     * @param ChainQueue $chainQueue
     */
    public function __construct(
        Connection $database,
        ChainQueue $chainQueue
    ) {
        $this->database = $database;
        $this->chainQueue = $chainQueue;
        parent::__construct();
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
                $this->trans('commands.user.password.reset.options.user-id')
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $uid = $input->getArgument('user');

        $user = User::load($uid);

        if (!$user) {
            $io->error(
                sprintf(
                    $this->trans('commands.user.password.reset.errors.invalid-user'),
                    $uid
                )
            );

            return 1;
        }

        $password = $input->getArgument('password');
        if (!$password) {
            $io->error(
                sprintf(
                    $this->trans('commands.user.password.reset.errors.empty-password'),
                    $uid
                )
            );

            return 1;
        }

        try {
            $user->setPassword($password);
            $user->save();

            $schema = $this->database->schema();
            $flood = $schema->findTables('flood');

            if ($flood) {
                $this-$this->chainQueue
                    ->addCommand('user:login:clear:attempts', ['uid' => $uid]);
            }
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return 1;
        }

        $io->success(
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
        $io = new DrupalStyle($input, $output);

        $user = $input->getArgument('user');
        if (!$user) {
            while (true) {
                $user = $io->ask(
                    $this->trans('commands.user.password.reset.questions.user'),
                    '',
                    function ($uid) use ($io) {
                        if ($uid) {
                            $uid = (int) $uid;
                            if (is_int($uid) && $uid > 0) {
                                return $uid;
                            } else {
                                $io->error(
                                    sprintf($this->trans('commands.user.password.reset.questions.invalid-uid'), $uid)
                                );

                                return false;
                            }
                        }
                    }
                );

                if ($user) {
                    break;
                }
            }

            $input->setArgument('user', $user);
        }

        $password = $input->getArgument('password');
        if (!$password) {
            while (true) {
                $password = $io->ask(
                    $this->trans('commands.user.password.hash.questions.password'),
                    '',
                    function ($pass) use ($io) {
                        if ($pass) {
                            if (!empty($pass)) {
                                return $pass;
                            } else {
                                $io->error(
                                    sprintf($this->trans('commands.user.password.hash.questions.invalid-pass'), $pass)
                                );

                                return false;
                            }
                        }
                    }
                );

                if ($password) {
                    break;
                }
            }


            $input->setArgument('password', $password);
        }
    }
}
