<?php
/**
 * @file
 * Contains \Drupal\Console\Command\Config\DeleteCommand.
 */

namespace Drupal\Console\Command\State;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;

class DeleteCommand extends Command
{
    use CommandTrait;

    /**
     * @var StateInterface
     */
    protected $state;

    /**
     * @var KeyValueFactoryInterface
     */
    protected $keyValue;

    /**
     * DeleteCommand constructor.
     *
     * @param StateInterface           $state
     * @param KeyValueFactoryInterface $keyValue
     */
    public function __construct(
        StateInterface $state,
        KeyValueFactoryInterface $keyValue
    ) {
        $this->state = $state;
        $this->keyValue = $keyValue;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('state:delete')
            ->setDescription($this->trans('commands.state.delete.description'))
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                $this->trans('commands.state.delete.arguments.name')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $name = $input->getArgument('name');
        if (!$name) {
            $names = array_keys($this->keyValue->get('state')->getAll());
            $name = $io->choiceNoList(
                $this->trans('commands.state.delete.arguments.name'),
                $names
            );
            $input->setArgument('name', $name);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $name = $input->getArgument('name');
        if (!$name) {
            $io->error($this->trans('commands.state.delete.messages.enter-name'));

            return 1;
        }

        if (!$this->state->get($name)) {
            $io->error(
                sprintf(
                    $this->trans('commands.state.delete.messages.state-not-exists'),
                    $name
                )
            );

            return 1;
        }

        try {
            $this->state->delete($name);
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return 1;
        }

        $io->success(
            sprintf(
                $this->trans('commands.state.delete.messages.deleted'),
                $name
            )
        );

        return 0;
    }
}
