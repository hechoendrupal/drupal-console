<?php
/**
 * @file
 * Contains \Drupal\Console\Command\Role\DeleteCommand.
 */

namespace Drupal\Console\Command\Role;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Console\Utils\DrupalApi;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\Console\Core\Style\DrupalStyle;

class DeleteCommand extends Command
{
    use ConfirmationTrait;

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
     * DeleteCommand constructor.
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
            ->setName('role:delete')
            ->setDescription($this->trans('commands.role.delete.description'))
            ->setHelp($this->trans('commands.role.delete.help'))
            ->addArgument(
                'roles',
                InputArgument::IS_ARRAY,
                $this->trans('commands.role.delete.argument.roles')
            )->setAliases(['rd']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $roles = $input->getArgument('roles');

        $role = $this->deleteRole(
            $roles
        );

        $tableHeader = [
            $this->trans('commands.role.delete.messages.role-id'),
            $this->trans('commands.role.delete.messages.role-name'),
        ];

        if ($role['success']) {
            $io->success(
                sprintf(
                    $this->trans('commands.role.delete.messages.role-created')
                )
            );

            $io->table($tableHeader, $role['success']);

            return 0;
        }

        if ($role['error']) {
            $io->error($role['error']['error']);

            return 1;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $rolename = $input->getArgument('roles');
        if (!$rolename) {
            $roles_collection = [];
            $roles = array_keys($this->drupalApi->getRoles());
            $io->writeln($this->trans('commands.common.questions.roles.message'));
            while (true) {
                $role = $io->choiceNoList(
                    $this->trans('commands.common.questions.roles.name'),
                    $roles,
                    null,
                    true
                );
                $role = trim($role);
                if (empty($role)) {
                    break;
                }

                array_push($roles_collection, $role);
                $role_key = array_search($role, $roles, true);
                if ($role_key >= 0) {
                    unset($roles[$role_key]);
                }
            }

            $input->setArgument('roles', $roles_collection);
        }
    }

    /**
     * Remove and returns an array of deleted roles
     *
     * @param $roles
     *
     * @return $array
     */
    private function deleteRole($roles)
    {
        $result = [];
        try {
            foreach ($roles as $value) {
                $role = $this->entityTypeManager->getStorage('user_role')->load($value);
                $this->entityTypeManager->getStorage('user_role')->delete([$role]);

                $result['success'][] = [
                    'role-id' => $value,
                    'role-name' => $value
                ];
            }
        } catch (\Exception $e) {
            $result['error'] = [
                'error' => 'Error: ' . get_class($e) . ', code: ' . $e->getCode() . ', message: ' . $e->getMessage()
            ];
        }

        return $result;
    }
}
