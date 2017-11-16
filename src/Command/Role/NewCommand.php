<?php
/**
 * @file
 * Contains \Drupal\Console\Command\Role\NewCommand.
 */

namespace Drupal\Console\Command\Role;

use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Utils\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Console\Utils\DrupalApi;
use Drupal\Console\Command\Shared\ConfirmationTrait;
use Drupal\user\Entity\Role;
use Drupal\Console\Core\Style\DrupalStyle;

class NewCommand extends Command
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
     * @var Validator
     */
    protected $validator;

    /**
     * @var StringConverter
     */
    protected $stringConverter;

    /**
     * NewCommand constructor.
     *
     * @param Connection                 $database
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param DateFormatterInterface     $dateFormatter
     * @param DrupalApi                  $drupalApi
     * @param Validator                  $validator
     * @param StringConverter            $stringConverter
     */
    public function __construct(
        Connection $database,
        EntityTypeManagerInterface $entityTypeManager,
        DateFormatterInterface $dateFormatter,
        DrupalApi $drupalApi,
        Validator $validator,
        StringConverter $stringConverter
    ) {
        $this->database = $database;
        $this->entityTypeManager = $entityTypeManager;
        $this->dateFormatter = $dateFormatter;
        $this->drupalApi = $drupalApi;
        $this->validator = $validator;
        $this->stringConverter = $stringConverter;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('role:new')
            ->setDescription($this->trans('commands.role.new.description'))
            ->setHelp($this->trans('commands.role.new.help'))
            ->addArgument(
                'rolename',
                InputArgument::OPTIONAL,
                $this->trans('commands.role.new.argument.rolename')
            )
            ->addArgument(
                'machine-name',
                InputArgument::OPTIONAL,
                $this->trans('commands.role.new.argument.machine-name')
            )->setAliases(['rn']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $rolename = $input->getArgument('rolename');
        $machine_name= $input->getArgument('machine-name');

        $role = $this->createRole(
            $rolename,
            $machine_name
        );

        $tableHeader = [
            $this->trans('commands.role.new.messages.role-id'),
            $this->trans('commands.role.new.messages.role-name'),
        ];

        if ($role['success']) {
            $io->table($tableHeader, $role['success']);

            $io->success(
                sprintf(
                    $this->trans('commands.role.new.messages.role-created'),
                    $role['success'][0]['role-name']
                )
            );

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

        $name = $input->getArgument('rolename');
        if (!$name) {
            $name = $io->ask($this->trans('commands.role.new.questions.rolename'));
            $input->setArgument('rolename', $name);
        }

        $machine_name = $input->getArgument('machine-name');

        if (!$machine_name) {
            $machine_name = $io->ask(
                $this->trans('commands.role.new.questions.machine-name'),
                $this->stringConverter->createMachineName($name),
                function ($machine_name) {
                    $roles = $this->drupalApi->getRoles();
                    if (array_key_exists($machine_name, $roles)) {
                        throw new \Exception('The machine name is already exist');
                    }

                    return $this->validator->validateMachineName($machine_name);
                }
            );
            $input->setArgument('machine-name', $machine_name);
        }
    }

    /**
     * Create and returns an array of new role
     *
     * @param $rolename
     * @param $machine_name
     *
     * @return $array
     */
    private function createRole($rolename, $machine_name)
    {
        $role = Role::create(
            [
                'id' => $machine_name,
                'label' => $rolename,
                'originalId' => $machine_name
            ]
        );

        $result = [];

        try {
            $role->save();

            $result['success'][] = [
                'role-id' => $role->id(),
                'role-name' => $role->get('label')
            ];
        } catch (\Exception $e) {
            $result['error'] = [
                'vid' => $role->id(),
                'name' => $role->get('label'),
                'error' => 'Error: ' . get_class($e) . ', code: ' . $e->getCode() . ', message: ' . $e->getMessage()
            ];
        }

        return $result;
    }
}
