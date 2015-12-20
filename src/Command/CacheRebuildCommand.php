<?php

/**
 * @file
 * Contains \Drupal\Console\Command\CacheRebuildCommand.
 */

namespace Drupal\Console\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Style\DrupalStyle;

/**
 * Class CacheRebuildCommand
 * @package Drupal\Console\Command
 */
class CacheRebuildCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cache:rebuild')
            ->setDescription($this->trans('commands.cache.rebuild.description'))
            ->addArgument(
                'cache',
                InputArgument::OPTIONAL,
                $this->trans('commands.cache.rebuild.options.cache')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $this->getDrupalHelper()->loadLegacyFile('/core/includes/utility.inc');

        $validators = $this->getValidator();

        // Get the --cache option and make validation
        $cache = $input->getArgument('cache');

        $validated_cache = $validators->validateCache($cache);
        if (!$validated_cache) {
            $io->error(
                sprintf(
                    $this->trans('commands.cache.rebuild.messages.invalid_cache'),
                    $cache
                )
            );

            return;
        }

        // Start rebuilding cache
        $io->newLine();
        $io->comment($this->trans('commands.cache.rebuild.messages.rebuild'));

        // Get data needed to rebuild cache
        $kernelHelper = $this->getKernelHelper();
        $classLoader = $kernelHelper->getClassLoader();
        $request = $kernelHelper->getRequest();

        // Check cache to rebuild
        if ($cache === 'all') {
            // If cache is all, then clear all caches
            drupal_rebuild($classLoader, $request);
        } else {
            // Else, clear the selected cache
            $caches = $validators->getCaches();
            $caches[$cache]->deleteAll();
        }

        $io->success($this->trans('commands.cache.rebuild.messages.completed'));
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $cache = $input->getArgument('cache');
        if (!$cache) {
            $validators = $this->getValidator();
            $caches = $validators->getCaches();
            $cache_keys = array_keys($caches);

            $cache = $io->choiceNoList(
                $this->trans('commands.cache.rebuild.questions.cache'),
                $cache_keys,
                'all'
            );

            $input->setArgument('cache', $cache);
        }
    }
}
