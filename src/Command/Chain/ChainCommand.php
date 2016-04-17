<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Chain\ChainCommand.
 */

namespace Drupal\Console\Command\Chain;

use Dflydev\PlaceholderResolver\DataSource\ArrayDataSource;
use Dflydev\PlaceholderResolver\RegexPlaceholderResolver;
use Drupal\Console\Command\ChainFilesTrait;
use Drupal\Console\Command\InputTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Command\Command;

/**
 * Class ChainCommand
 * @package Drupal\Console\Command\Chain
 */
class ChainCommand extends Command
{
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

        if (!$file) {
            $files = $this->getChainFiles(true);

            $file = $io->choice(
                $this->trans('commands.chain.questions.chain-file'),
                array_values($files)
            );

            $input->setOption('file', $file);
        }
        $chainContent = file_get_contents($file);

        $placeholder = $input->getOption('placeholder');
        $inlinePlaceHolders = $this->extractInlinePlaceHolders($chainContent);

        if (!$placeholder && $inlinePlaceHolders) {
            foreach($inlinePlaceHolders as $inlinePlaceHolder) {
                $placeholder[] = sprintf(
                    '%s:%s',
                    $inlinePlaceHolder,
                    $io->ask(
                        sprintf(
                          'Enter placeholder value for <comment>%s</comment>',
                          $inlinePlaceHolder
                        )
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

        if (!$file) {
            $io->error($this->trans('commands.chain.messages.missing_file'));

            return 1;
        }

        if (strpos($file, '~') === 0) {
            $home = rtrim(getenv('HOME') ?: getenv('USERPROFILE'), '/');
            $file = realpath(preg_replace('/~/', $home, $file, 1));
        }

        if (!(strpos($file, '/') === 0)) {
            $file = sprintf('%s/%s', getcwd(), $file);
        }

        if (!file_exists($file)) {
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
        foreach($environmentPlaceHolders as $envPlaceHolder){
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
                'Missing environment placeholder(s) %s',
                implode(', ', array_keys($missingEnvironmentPlaceHolders))
              )
            );

            $io->info('You can set environment placeholders as:');
            $io->block(array_values($missingEnvironmentPlaceHolders));

            return 1;
        }

        $envPlaceHolderData = new ArrayDataSource($envPlaceHolderMap);
        $placeholderResolver = new RegexPlaceholderResolver($envPlaceHolderData, '${{', '}}');
        $chainContent = $placeholderResolver->resolvePlaceholder($chainContent);

        $inlinePlaceHolders = $this->extractInlinePlaceHolders($chainContent);

        var_export($placeholder);
        var_export($inlinePlaceHolders);

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
                'Missing inline placeholder(s) %s',
                implode(', ', array_keys($missingInlinePlaceHolders))
              )
            );

            $io->info('You can pass inline placeholders as:');
            $io->block(array_values($missingInlinePlaceHolders));

            return 1;
        }

        var_export($inlinePlaceHolderMap);

        $inlinePlaceHolderData = new ArrayDataSource($inlinePlaceHolderMap);

        var_export($inlinePlaceHolderData);

        $placeholderResolver = new RegexPlaceholderResolver($inlinePlaceHolderData, '{{', '}}');
        $chainContent = $placeholderResolver->resolvePlaceholder($chainContent);

        var_export($chainContent);

        $parser = $this->getContainerHelper()->get('parser');
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

            $this->getChain()
                ->addCommand(
                    $command['command'],
                    $moduleInputs,
                    $interactive,
                    $learning
                );
        }
    }
}
