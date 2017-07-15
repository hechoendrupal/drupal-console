<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Views\DisableCommand.
 */

namespace Drupal\Console\Command\Views;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Console\Core\Style\DrupalStyle;

/**
 * Class DisableCommand
 *
 * @package Drupal\Console\Command\Views
 */
class DisableCommand extends Command
{
    use CommandTrait;

    /**
     * @var EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * @var QueryFactory
     */
    protected $entityQuery;

    /**
     * DisableCommand constructor.
     *
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param QueryFactory               $entityQuery
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        QueryFactory $entityQuery
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->entityQuery = $entityQuery;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('views:disable')
            ->setDescription($this->trans('commands.views.disable.description'))
            ->addArgument(
                'view-id',
                InputArgument::OPTIONAL,
                $this->trans('commands.views.debug.arguments.view-id')
            )
            ->setAliases(['vd']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $viewId = $input->getArgument('view-id');
        if (!$viewId) {
            $views = $this->entityQuery
                ->get('view')
                ->condition('status', 1)
                ->execute();
            $viewId = $io->choiceNoList(
                $this->trans('commands.views.debug.arguments.view-id'),
                $views
            );
            $input->setArgument('view-id', $viewId);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $viewId = $input->getArgument('view-id');

        $view = $this->entityTypeManager->getStorage('view')->load($viewId);

        if (empty($view)) {
            $io->error(sprintf($this->trans('commands.views.debug.messages.not-found'), $viewId));

            return 1;
        }

        try {
            $view->disable()->save();

            $io->success(sprintf($this->trans('commands.views.disable.messages.disabled-successfully'), $view->get('label')));
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return 1;
        }

        return 0;
    }
}
