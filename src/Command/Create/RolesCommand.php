<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Create\RolesCommand.
 */

namespace Drupal\Console\Command\Create;

use Drupal\Console\Utils\Create\RoleData;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\Command;

/**
 * Class RolesCommand
 *
 * @package Drupal\Console\Command\Create
 */
class RolesCommand extends Command
{
    /**
     * @var RoleData
     */
    protected $createRoleData;

    /**
     * RolesCommand constructor.
     *
     * @param RoleData $createRoleData
     */
    public function __construct(
        RoleData $createRoleData
    ) {
        $this->createRoleData = $createRoleData;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('create:roles')
            ->setDescription($this->trans('commands.create.roles.description'))
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.create.roles.options.limit')
            )
            ->setAliases(['crr']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $limit = $input->getOption('limit');
        if (!$limit) {
            $limit = $this->getIo()->ask(
                $this->trans('commands.create.roles.questions.limit'),
                5
            );
            $input->setOption('limit', $limit);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $limit = $input->getOption('limit')?:5;

        $result = $this->createRoleData->create(
            $limit
        );

        $tableHeader = [
            $this->trans('commands.create.roles.messages.role-id'),
            $this->trans('commands.create.roles.messages.role-name'),
        ];

        if ($result['success']) {
            $this->getIo()->table($tableHeader, $result['success']);

            $this->getIo()->success(
                sprintf(
                    $this->trans('commands.create.roles.messages.created-roles'),
                    count($result['success'])
                )
            );
        }

        if (isset($result['error'])) {
            foreach ($result['error'] as $error) {
                $this->getIo()->error(
                    sprintf(
                        $this->trans('commands.create.roles.messages.error'),
                        $error
                    )
                );
            }
        }

        return 0;
    }
}
