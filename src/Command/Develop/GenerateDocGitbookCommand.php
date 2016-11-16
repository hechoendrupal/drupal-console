<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Develop\GenerateDocGitbookCommand.
 */

namespace Drupal\Console\Command\Develop;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Utils\TwigRenderer;

class GenerateDocGitbookCommand extends Command
{
    use CommandTrait;
    /**
     * @var TwigRenderer $renderer
     */
    protected $renderer;

    /**
     * GenerateDocGitbookCommand constructor.
     * @param TwigRenderer $renderer
     */
    public function __construct(TwigRenderer $renderer)
    {
        $this->renderer = $renderer;
        parent::__construct();
    }


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:doc:gitbook')
            ->setDescription($this->trans('commands.generate.doc.gitbook.description'))
            ->addOption(
                'path',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.doc.gitbook.options.path')
            );
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        $path = null;
        if ($input->hasOption('path')) {
            $path = $input->getOption('path');
        }

        if (!$path) {
            $io->error(
                $this->trans('commands.generate.doc.gitbook.messages.missing_path')
            );

            return 1;
        }

        $application = $this->getApplication();
        $applicationData = $application->getData();
        $namespaces = $applicationData['application']['namespaces'];
        foreach ($namespaces as $namespace) {
            foreach ($applicationData['commands'][$namespace] as $command) {
                $this->renderFile(
                    'gitbook' . DIRECTORY_SEPARATOR . 'command.md.twig',
                    $path . DIRECTORY_SEPARATOR . 'commands' . DIRECTORY_SEPARATOR . $command['dashed'] . '.md',
                    $command,
                    null,
                    $this->renderer
                );
            }
        }

        $this->renderFile(
            'gitbook'.DIRECTORY_SEPARATOR.'available-commands.md.twig',
            $path . DIRECTORY_SEPARATOR . 'commands'.DIRECTORY_SEPARATOR.'available-commands.md',
            $applicationData,
            null,
            $this->renderer
        );

        $this->renderFile(
            'gitbook'.DIRECTORY_SEPARATOR.'available-commands-list.md.twig',
            $path . DIRECTORY_SEPARATOR . 'commands'.DIRECTORY_SEPARATOR.'available-commands-list.md',
            $applicationData,
            null,
            $this->renderer
        );
    }

    private function renderFile($template, $target, $parameters, $flag = null, $renderer)
    {
        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0777, true);
        }

        return file_put_contents($target, $renderer->render($template, $parameters), $flag);
    }
}
