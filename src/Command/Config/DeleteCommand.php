<?php
/**
 * @file
 * Contains \Drupal\Console\Command\Config\DeleteCommand.
 */

namespace Drupal\Console\Command\Config;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Exception\RuntimeException;
use Symfony\Component\Console\Command\Command;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\CachedStorage;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;

class DeleteCommand extends Command
{
    use CommandTrait;

    protected $allConfig = [];

    /**
     * @var ConfigFactory
     */
    protected $configFactory;

    /**
     * @var CachedStorage
     */
    protected $configStorage;

    /**
     * @var StorageInterface
     */
    protected $configStorageSync;

    /**
     * DeleteCommand constructor.
     *
     * @param ConfigFactory    $configFactory
     * @param CachedStorage    $configStorage
     * @param StorageInterface $configStorageSync
     */
    public function __construct(
        ConfigFactory $configFactory,
        CachedStorage $configStorage,
        StorageInterface $configStorageSync
    ) {
        $this->configFactory = $configFactory;
        $this->configStorage = $configStorage;
        $this->configStorageSync = $configStorageSync;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('config:delete')
            ->setDescription($this->trans('commands.config.delete.description'))
            ->addArgument(
                'type',
                InputArgument::OPTIONAL,
                $this->trans('commands.config.delete.arguments.type')
            )
            ->addArgument(
                'name',
                InputArgument::OPTIONAL,
                $this->trans('commands.config.delete.arguments.name')
            )->setAliases(['cd']);
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $type = $input->getArgument('type');
        if (!$type) {
            $type = $io->choiceNoList(
                $this->trans('commands.config.delete.arguments.type'),
                ['active', 'staging'],
                'active'
            );
            $input->setArgument('type', $type);
        }

        $name = $input->getArgument('name');
        if (!$name) {
            $name = $io->choiceNoList(
                $this->trans('commands.config.delete.arguments.name'),
                $this->getAllConfigNames(),
                'all'
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

        $type = $input->getArgument('type');
        if (!$type) {
            $io->error($this->trans('commands.config.delete.errors.type'));
            return 1;
        }

        $name = $input->getArgument('name');
        if (!$name) {
            $io->error($this->trans('commands.config.delete.errors.name'));
            return 1;
        }

        $configStorage = ('active' === $type) ? $this->configStorage : $this->configStorageSync;

        if (!$configStorage) {
            $io->error($this->trans('commands.config.delete.errors.config-storage'));
            return 1;
        }

        if ('all' === $name) {
            $io->commentBlock($this->trans('commands.config.delete.warnings.undo'));
            if ($io->confirm($this->trans('commands.config.delete.questions.sure'))) {
                if ($configStorage instanceof FileStorage) {
                    $configStorage->deleteAll();
                } else {
                    foreach ($this->yieldAllConfig() as $name) {
                        $this->removeConfig($name);
                    }
                }

                $io->success($this->trans('commands.config.delete.messages.all'));

                return 0;
            }
        }

        if ($configStorage->exists($name)) {
            if ($configStorage instanceof FileStorage) {
                $configStorage->delete($name);
            } else {
                $this->removeConfig($name);
            }

            $io->success(
                sprintf(
                    $this->trans('commands.config.delete.messages.deleted'),
                    $name
                )
            );
            return 0;
        }

        $message = sprintf($this->trans('commands.config.delete.errors.not-exists'), $name);
        $io->error($message);

        return 1;
    }

    /**
     * Retrieve configuration names form cache or service factory.
     *
     * @return array
     *   All configuration names.
     */
    private function getAllConfigNames()
    {
        if ($this->allConfig) {
            return $this->allConfig;
        }

        foreach ($this->configFactory->listAll() as $name) {
            $this->allConfig[] = $name;
        }

        return $this->allConfig;
    }

    /**
     * Yield configuration names.
     *
     * @return \Generator
     *   Yield generator with config name.
     */
    private function yieldAllConfig()
    {
        $this->allConfig = $this->allConfig ?: $this->getAllConfigNames();
        foreach ($this->allConfig as $name) {
            yield $name;
        }
    }

    /**
     * Delete given config name.
     *
     * @param String $name Given config name.
     */
    private function removeConfig($name)
    {
        try {
            $this->configFactory->getEditable($name)->delete();
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage());
        }
    }
}
