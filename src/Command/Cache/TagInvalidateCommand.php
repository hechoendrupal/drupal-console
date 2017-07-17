<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Cache\TagInvalidateCommand.
 */

namespace Drupal\Console\Command\Cache;

use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TagInvalidateCommand extends Command
{
    use CommandTrait;

    /**
     * @var CacheTagsInvalidatorInterface
     */
    protected $invalidator;

    /**
     * TagInvalidateCommand constructor.
     *
     * @param CacheTagsInvalidatorInterface $invalidator
     */
    public function __construct(CacheTagsInvalidatorInterface $invalidator)
    {
        parent::__construct();
        $this->invalidator = $invalidator;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cache:tag:invalidate')
            ->setDescription($this->trans('commands.cache.tag.invalidate.description'))
            ->addArgument(
                'tag',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                $this->trans('commands.cache.tag.invalidate.options.tag')
            )
            ->setAliases(['cti']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $tags = $input->getArgument('tag');

        $io->comment(
            sprintf(
                $this->trans('commands.cache.tag.invalidate.messages.start'),
                implode(', ', $tags)
            )
        );

        $this->invalidator->invalidateTags($tags);
        $io->success($this->trans('commands.cache.tag.invalidate.messages.completed'));
    }
}
