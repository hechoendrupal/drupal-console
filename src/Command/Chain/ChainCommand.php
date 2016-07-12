<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Chain\ChainCommand.
 */

namespace Drupal\Console\Command\Chain;

use Dflydev\PlaceholderResolver\DataSource\ArrayDataSource;
use Dflydev\PlaceholderResolver\RegexPlaceholderResolver;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Command\Shared\ChainFilesTrait;
use Drupal\Console\Command\Shared\InputTrait;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Command\Shared\CommandTrait;

/**
 * Class ChainCommand
 * @package Drupal\Console\Command\Chain
 */
class ChainCommand extends Command
{
    use CommandTrait;
    use ChainFilesTrait;
    use InputTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('chain')
            ->setDescription($this->trans('commands.chain.description'))
            ->addOption(
                'file',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.chain.options.file')
            )
            ->addOption(
                'placeholder',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                $this->trans('commands.chain.options.placeholder')
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);
        $file = $input->getOption('file');
        $fileUtil = $this->getApplication()->getContainerHelper()->get('file_util');

        if (!$file) {
            $files = $this->getChainFiles(true);

            $file = $io->choice(
                $this->trans('commands.chain.questions.chain-file'),
                array_values($files)
            );
        }

        $file = $fileUtil->calculateRealPath($file);
        $input->setOption('file', $file);

        $chainContent = file_get_contents($file);

        $placeholder = $input->getOption('placeholder');
        $inlinePlaceHolders = $this->extractInlinePlaceHolders($chainContent);

        if (!$placeholder && $inlinePlaceHolders) {
            foreach ($inlinePlaceHolders as $key => $inlinePlaceHolder) {
                $inlinePlaceHolderDefault = '';
                if (strpos($inlinePlaceHolder, '|')>0) {
                    $placeholderParts = explode('|', $inlinePlaceHolder);
                    $inlinePlaceHolder = $placeholderParts[0];
                    $inlinePlaceHolderDefault = $placeholderParts[1];
                    $inlinePlaceHolders[$key] = $inlinePlaceHolder;
                }

                $placeholder[] = sprintf(
                    '%s:%s',
                    $inlinePlaceHolder,
                    $io->ask(
                        sprintf(
                            'Enter placeholder value for <comment>%s</comment>',
                            $inlinePlaceHolder
                        ),
                        $inlinePlaceHolderDefault
                    )
                );
            }
            $input->setOption('placeholder', $placeholder);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $interactive = false;
        $learning = $input->hasOption('learning')?$input->getOption('learning'):false;

        $file = $input->getOption('file');
        $fileUtil = $this->getApplication()->getContainerHelper()->get('file_util');
        $fileSystem = $this->getApplication()->getContainerHelper()->get('filesystem');

        if (!$file) {
            $io->error($this->trans('commands.chain.messages.missing_file'));

            return 1;
        }

        $file = $fileUtil->calculateRealPath($file);

        if (!$fileSystem->exists($file)) {
            $io->error(
                sprintf(
                    $this->trans('commands.chain.messages.invalid_file'),
                    $file
                )
            );

            return 1;
        }

        $placeholder = $input->getOption('placeholder');
        if ($placeholder) {
            $placeholder = $this->inlineValueAsArray($placeholder);
        }

        $chainContent = file_get_contents($file);
        $environmentPlaceHolders = $this->extractEnvironmentPlaceHolders($chainContent);

        $envPlaceHolderMap = [];
        $missingEnvironmentPlaceHolders = [];
        foreach ($environmentPlaceHolders as $envPlaceHolder) {
            if (!getenv($envPlaceHolder)) {
                $missingEnvironmentPlaceHolders[$envPlaceHolder] = sprintf(
                    'export %s=%s_VALUE',
                    $envPlaceHolder,
                    strtoupper($envPlaceHolder)
                );

                continue;
            }
            $envPlaceHolderMap[$envPlaceHolder] = getenv($envPlaceHolder);
        }

        if ($missingEnvironmentPlaceHolders) {
            $io->error(
                sprintf(
                    $this->trans('commands.chain.messages.missing-environment-placeholders'),
                    implode(', ', array_keys($missingEnvironmentPlaceHolders))
                )
            );

            $io->info($this->trans('commands.chain.messages.set-environment-placeholders'));
            $io->block(array_values($missingEnvironmentPlaceHolders));

            return 1;
        }

        $envPlaceHolderData = new ArrayDataSource($envPlaceHolderMap);
        $placeholderResolver = new RegexPlaceholderResolver($envPlaceHolderData, '${{', '}}');
        $chainContent = $placeholderResolver->resolvePlaceholder($chainContent);

        $inlinePlaceHolders = $this->extractInlinePlaceHolders($chainContent);

        $inlinePlaceHoldersReplacements = [];
        foreach ($inlinePlaceHolders as $key => $inlinePlaceHolder) {
            if (strpos($inlinePlaceHolder, '|') > 0) {
                $placeholderParts = explode('|', $inlinePlaceHolder);
                $inlinePlaceHoldersReplacements[] = $placeholderParts[0];
                continue;
            }
            $inlinePlaceHoldersReplacements[] = $inlinePlaceHolder;
        }

        $chainContent = str_replace(
            $inlinePlaceHolders,
            $inlinePlaceHoldersReplacements,
            $chainContent
        );

        $inlinePlaceHolders = $inlinePlaceHoldersReplacements;

        $inlinePlaceHolderMap = [];
        foreach ($placeholder as $key => $placeholderItem) {
            $inlinePlaceHolderMap = array_merge($inlinePlaceHolderMap, $placeholderItem);
        }

        $missingInlinePlaceHolders = [];
        foreach ($inlinePlaceHolders as $inlinePlaceHolder) {
            if (!array_key_exists($inlinePlaceHolder, $inlinePlaceHolderMap)) {
                $missingInlinePlaceHolders[$inlinePlaceHolder] = sprintf(
                    '--placeholder="%s:%s_VALUE"',
                    $inlinePlaceHolder,
                    strtoupper($inlinePlaceHolder)
                );
            }
        }

        if ($missingInlinePlaceHolders) {
            $io->error(
                sprintf(
                    $this->trans('commands.chain.messages.missing-inline-placeholders'),
                    implode(', ', array_keys($missingInlinePlaceHolders))
                )
            );

            $io->info($this->trans('commands.chain.messages.set-inline-placeholders'));
            $io->block(array_values($missingInlinePlaceHolders));

            return 1;
        }

        $inlinePlaceHolderData = new ArrayDataSource($inlinePlaceHolderMap);
        $placeholderResolver = new RegexPlaceholderResolver($inlinePlaceHolderData, '%{{', '}}');
        $chainContent = $placeholderResolver->resolvePlaceholder($chainContent);

        $parser = $this->getApplication()->getContainerHelper()->get('parser');
        $configData = $parser->parse($chainContent);

        $commands = [];
        if (array_key_exists('commands', $configData)) {
            $commands = $configData['commands'];
        }

        foreach ($commands as $command) {
            $moduleInputs = [];
            $arguments = !empty($command['arguments']) ? $command['arguments'] : [];
            $options = !empty($command['options']) ? $command['options'] : [];

            foreach ($arguments as $key => $value) {
                $moduleInputs[$key] = is_null($value) ? '' : $value;
            }

            foreach ($options as $key => $value) {
                $moduleInputs['--'.$key] = is_null($value) ? '' : $value;
            }

            $parameterOptions = $input->getOptions();
            unset($parameterOptions['file']);
            foreach ($parameterOptions as $key => $value) {
                if ($value===true) {
                    $moduleInputs['--' . $key] = true;
                }
            }

            $this->get('chain_queue')
                ->addCommand(
                    $command['command'],
                    $moduleInputs,
                    $interactive,
                    $learning
                );
        }
    }
}
