<?php
/**
 * @file
 * Contains \Drupal\Console\Command\User\CreateCommand.
 */

namespace Drupal\Console\Command\User;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Console\Utils\DrupalApi;
use Drupal\user\Entity\User;

class CreateCommand extends Command
{

    /**
     * @var Connection
     */
    protected $database;

    /**
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * @var DateFormatterInterface
     */
    protected $dateFormatter;

    /**
     * @var DrupalApi
     */
    protected $drupalApi;

    /**
     * CreateCommand constructor.
     *
     * @param Connection                 $database
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param DateFormatterInterface     $dateFormatter
     * @param DrupalApi                  $drupalApi
     */
    public function __construct(
        Connection $database,
        EntityTypeManagerInterface $entityTypeManager,
        DateFormatterInterface $dateFormatter,
        DrupalApi $drupalApi
    ) {
        $this->database = $database;
        $this->entityTypeManager = $entityTypeManager;
        $this->dateFormatter = $dateFormatter;
        $this->drupalApi = $drupalApi;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('user:create')
            ->setDescription($this->trans('commands.user.create.description'))
            ->setHelp($this->trans('commands.user.create.help'))
            ->addArgument(
                'username',
                InputArgument::OPTIONAL,
                $this->trans('commands.user.create.options.username')
            )
            ->addArgument(
                'password',
                InputArgument::OPTIONAL,
                $this->trans('commands.user.create.options.password')
            )
            ->addOption(
                'roles',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.user.create.options.roles')
            )
            ->addOption(
                'email',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.user.create.options.email')
            )
            ->addOption(
                'status',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.user.create.options.status')
            )->setAliases(['uc']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');
        $roles = $input->getOption('roles');
        if (is_string($roles)) {
            $roles = explode(',', $roles);
        }
        $email = $input->getOption('email');
        $status = $input->getOption('status');

        $user = $this->createUser(
            $username,
            $password,
            $roles,
            $email,
            $status
        );

        $tableHeader = ['Field', 'Value'];

        $tableFields = [
            $this->trans('commands.user.create.messages.user-id'),
            $this->trans('commands.user.create.messages.username'),
            $this->trans('commands.user.create.messages.password'),
            $this->trans('commands.user.create.messages.email'),
            $this->trans('commands.user.create.messages.roles'),
            $this->trans('commands.user.create.messages.created'),
            $this->trans('commands.user.create.messages.status'),
        ];

        if ($user['success']) {
            $tableData = array_map(
                function ($field, $value) {
                    return [$field, $value];
                },
                $tableFields,
                $user['success']
            );

            $this->getIo()->table($tableHeader, $tableData);

            $this->getIo()->success(
                sprintf(
                    $this->trans('commands.user.create.messages.user-created'),
                    $user['success']['username']
                )
            );

            return 0;
        }

        if ($user['error']) {
            $this->getIo()->error($user['error']['error']);

            return 1;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');
        if (!$username) {
            $username = $this->getIo()->ask(
                $this->trans('commands.user.create.questions.username')
            );

            $input->setArgument('username', $username);
        }

        $password = $input->getArgument('password');
        if (!$password) {
            $password = $this->getIo()->askEmpty(
                $this->trans('commands.user.create.questions.password')
            );

            $input->setArgument('password', $password);
        }

        $roles = $input->getOption('roles');
        if (!$roles) {
            $systemRoles = $this->drupalApi->getRoles(true, true, false);
            $roles = $this->getIo()->choice(
                $this->trans('commands.user.create.questions.roles'),
                array_values($systemRoles),
                null,
                true
            );

            $roles = array_map(
                function ($role) use ($systemRoles) {
                    return array_search($role, $systemRoles);
                },
                $roles
            );

            $input->setOption('roles', $roles);
        }

        $email = $input->getOption('email');
        if (!$email) {
            $email = $this->getIo()->askEmpty(
                $this->trans('commands.user.create.questions.email')
            );

            $input->setOption('email', $email);
        }

        $status = $input->getOption('status');
        if (!$status) {
            $status = $this->getIo()->choice(
                $this->trans('commands.user.create.questions.status'),
                [0, 1],
                1
            );

            $input->setOption('status', $status);
        }
    }

    private function createUser($username, $password, $roles, $email = null, $status = null)
    {
        $user = User::create(
            [
                'name' => $username,
                'mail' => $email ?: $username . '@example.com',
                'pass' => $password?:user_password(),
                'status' => $status,
                'roles' => $roles,
                'created' => \Drupal::time()->getRequestTime(),
            ]
        );

        $result = [];

        try {
            $user->save();

            $result['success'] = [
                'user-id' => $user->id(),
                'username' => $user->getUsername(),
                'password' => $password,
                'email' => $user->getEmail(),
                'roles' => implode(', ', $roles),
                'created' => $this->dateFormatter->format(
                    $user->getCreatedTime(),
                    'custom',
                    'Y-m-d h:i:s'
                ),
                'status' => $status

            ];
        } catch (\Exception $e) {
            $result['error'] = [
                'vid' => $user->id(),
                'name' => $user->get('name'),
                'error' => 'Error: ' . get_class($e) . ', code: ' . $e->getCode() . ', message: ' . $e->getMessage()
            ];
        }

        return $result;
    }
}
