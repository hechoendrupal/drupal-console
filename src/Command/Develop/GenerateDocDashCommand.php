<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Develop\GenerateDocDashCommand.
 */

namespace Drupal\Console\Command\Develop;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Style\DrupalStyle;
use Drupal\Console\Command\Shared\CommandTrait;
use Drupal\Console\Utils\TwigRenderer;
use Drupal\Console\Utils\ConfigurationManager;

class GenerateDocDashCommand extends Command
{
    use CommandTrait;

    /**
     * @var TwigRenderer $renderer
     */
    protected $renderer;
    protected $consoleRoot;
    /**
     * @constant Contents of the plist file required by the docset format.
     */
    const PLIST = <<<PLIST
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
	<key>CFBundleIdentifier</key>
	<string>drupalconsole</string>
	<key>CFBundleName</key>
	<string>Drupal Console</string>
	<key>DocSetPlatformFamily</key>
	<string>drupalconsole</string>
	<key>isDashDocset</key>
	<true/>
	<key>dashIndexFilePath</key>
    <string>index.html</string>
</dict>
</plist>
PLIST;

    private $single_commands = [
      'about',
      'chain',
      'drush',
      'help',
      'init',
      'list',
      'self-update'
    ];

    /**
     * @var SQLite3 Controller for the sqlite db required by the docset format.
     */
    private $sqlite;

    /**
     * @var ConfigurationManager $configurationManager
     */
    protected $configurationManager;

    /**
     * GenerateDocDashCommand constructor.
     * @param $renderer
     * @param $consoleRoot
     */
    public function __construct(TwigRenderer $renderer, $consoleRoot)
    {
        $this->renderer = $renderer;
        $this->consoleRoot = $consoleRoot;
        parent::__construct();
    }



    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:doc:dash')
            ->setDescription($this->trans('commands.generate.doc.dash.description'))
            ->addOption(
                'path',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.doc.dash.options.path')
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
                $this->trans('commands.generate.doc.dash.messages.missing_path')
            );

            return 1;
        }

        // Setup the docset structure
        $this->initDocset($path);

        $application = $this->getApplication();
        $command_list = [];

        foreach ($this->single_commands as $single_command) {
            $command = $application->find($single_command);
            $command_list['none'][] = [
              'name' => $command->getName(),
              'description' => $command->getDescription(),
            ];
            $this->renderCommand($command, $path, $this->renderer);
            $this->registerCommand($command, $path);
        }

        $namespaces = $application->getNamespaces();
        sort($namespaces);

        $namespaces = array_filter(
            $namespaces, function ($item) {
                return (strpos($item, ':') <= 0);
            }
        );

        foreach ($namespaces as $namespace) {
            $commands = $application->all($namespace);

            usort(
                $commands, function ($cmd1, $cmd2) {
                    return strcmp($cmd1->getName(), $cmd2->getName());
                }
            );

            foreach ($commands as $command) {
                if ($command->getModule() == 'Console') {
                    $command_list[$namespace][] = [
                      'name' => $command->getName(),
                      'description' => $command->getDescription(),
                    ];
                    $this->renderCommand($command, $path, $this->renderer);
                    $this->registerCommand($command, $path);
                }
            }
        }

        $input = $application->getDefinition();
        $options = $input->getOptions();
        $arguments = $input->getArguments();
        $parameters = [
          'command_list' => $command_list,
          'options' => $options,
          'arguments' => $arguments,
          'css_path' => 'style.css'
        ];

        // Set the index page
        $this->renderFile(
            'dash/index.html.twig',
            $path . '/DrupalConsole.docset/Contents/Resources/Documents/index.html',
            $parameters,
            null,
            $this->renderer
        );
    }

    private function renderCommand($command, $path, $renderer)
    {
        $input = $command->getDefinition();
        $options = $input->getOptions();
        $arguments = $input->getArguments();

        $parameters = [
          'options' => $options,
          'arguments' => $arguments,
          'command' => $command->getName(),
          'description' => $command->getDescription(),
          'aliases' => $command->getAliases(),
          'css_path' => '../style.css'
        ];

        $this->renderFile(
            'dash/generate-doc.html.twig',
            $path . '/DrupalConsole.docset/Contents/Resources/Documents/commands/'
            . str_replace(':', '-', $command->getName()) . '.html',
            $parameters,
            null,
            $renderer
        );
    }

    private function registerCommand($command)
    {
        try {
            $statement = $this->sqlite->prepare('INSERT OR IGNORE INTO searchIndex(name, type, path) VALUES (:name, :type, :path)');
            $statement->bindValue(':name', $command->getName(), SQLITE3_TEXT);
            $statement->bindValue(':type', 'Command', SQLITE3_TEXT);
            $statement->bindValue(
                ':path',
                'commands/'
                . str_replace(':', '-', $command->getName()) . '.html',
                SQLITE3_TEXT
            );
            $statement->execute();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function renderFile(
        $template,
        $target,
        $parameters,
        $renderer
    ) {
        $filesystem = new Filesystem();
        try {
            $filesystem->dumpFile(
                $target,
                $renderer->render($template, $parameters)
            );
        } catch (IOException $e) {
            throw $e;
        }
    }

    private function initDocset($path)
    {
        try {
            $filesystem = new Filesystem();
            $filesystem->mkdir(
                $path . '/DrupalConsole.docset/Contents/Resources/Documents/',
                0777
            );
            $filesystem->dumpFile(
                $path . '/DrupalConsole.docset/Contents/Info.plist',
                self::PLIST
            );

            $filesystem->copy(
                $this->consoleRoot . '/resources/drupal-console.png',
                $path . '/DrupalConsole.docset/icon.png'
            );
            $filesystem->copy(
                $this->consoleRoot . '/resources/dash.css',
                $path . '/DrupalConsole.docset/Contents/Resources/Documents/style.css'
            );
            // create the required sqlite db
            $this->sqlite = new \SQLite3($path . '/DrupalConsole.docset/Contents/Resources/docSet.dsidx');
            $this->sqlite->query("CREATE TABLE searchIndex(id INTEGER PRIMARY KEY, name TEXT, type TEXT, path TEXT)");
            $this->sqlite->query("CREATE UNIQUE INDEX anchor ON searchIndex (name, type, path)");
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
