<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Test\DebugCommand.
 */

namespace Drupal\Console\Command\Test;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Component\Utility\Timer;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Console\Core\Style\DrupalStyle;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\simpletest\TestDiscovery;
use Drupal\Core\Datetime\DateFormatter;

/**
 * @DrupalCommand(
 *     extension = "simpletest",
 *     extensionType = "module",
 * )
 */
class RunCommand extends Command
{
    use CommandTrait;

    /**
     * @var string
     */
    protected $appRoot;

    /**
      * @var TestDiscovery
      */
    protected $test_discovery;


    /**
     * @var ModuleHandlerInterface
     */
    protected $moduleHandler;


    /**
     * @var DateFormatter
     */
    protected $dateFormatter;

    /**
     * RunCommand constructor.
     *
     * @param Site                   $site
     * @param TestDiscovery          $test_discovery
     * @param ModuleHandlerInterface $moduleHandler
     */
    public function __construct(
        $appRoot,
        TestDiscovery $test_discovery,
        ModuleHandlerInterface $moduleHandler,
        DateFormatter $dateFormatter
    ) {
        $this->appRoot = $appRoot;
        $this->test_discovery = $test_discovery;
        $this->moduleHandler = $moduleHandler;
        $this->dateFormatter = $dateFormatter;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('test:run')
            ->setDescription($this->trans('commands.test.run.description'))
            ->addArgument(
                'test-class',
                InputArgument::REQUIRED,
                $this->trans('commands.test.run.arguments.test-class')
            )
            ->addArgument(
                'test-methods',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                $this->trans('commands.test.run.arguments.test-methods')
            )
            ->addOption(
                'url',
                null,
                InputOption::VALUE_REQUIRED,
                $this->trans('commands.test.run.arguments.url')
            )
            ->setAliases(['ter']);
    }

    /*
     * Set Server variable to be used in test cases.
     */

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        //Registers namespaces for disabled modules.
        $this->test_discovery->registerTestNamespaces();

        $testClass = $input->getArgument('test-class');
        $testMethods = $input->getArgument('test-methods');

        $url = $input->getOption('url');

        if (!$url) {
            $io->error($this->trans('commands.test.run.messages.url-required'));
            return null;
        }

        $this->setEnvironment($url);

        // Create simpletest test id
        $testId = db_insert('simpletest_test_id')
          ->useDefaults(['test_id'])
          ->execute();

        if (is_subclass_of($testClass, 'PHPUnit_Framework_TestCase')) {
            $io->info($this->trans('commands.test.run.messages.phpunit-pending'));
            return null;
        } else {
            if (!class_exists($testClass)) {
                $io->error(
                    sprintf(
                        $this->trans('commands.test.run.messages.invalid-class'),
                        $testClass
                    )
                );

                return 1;
            }

            $test = new $testClass($testId);
            $io->info($this->trans('commands.test.run.messages.starting-test'));
            Timer::start('run-tests');

            $test->run($testMethods);

            $end = Timer::stop('run-tests');

            $io->simple(
                $this->trans('commands.test.run.messages.test-duration') . ': ' .  $this->dateFormatter->formatInterval($end['time'] / 1000)
            );
            $io->simple(
                $this->trans('commands.test.run.messages.test-pass') . ': ' . $test->results['#pass']
            );
            $io->commentBlock(
                $this->trans('commands.test.run.messages.test-fail') . ': ' . $test->results['#fail']
            );
            $io->commentBlock(
                $this->trans('commands.test.run.messages.test-exception') . ': ' . $test->results['#exception']
            );
            $io->simple(
                $this->trans('commands.test.run.messages.test-debug') . ': ' . $test->results['#debug']
            );

            $this->moduleHandler->invokeAll(
                'test_finished',
                [$test->results]
            );

            $io->newLine();
            $io->info($this->trans('commands.test.run.messages.test-summary'));
            $io->newLine();

            $currentClass = null;
            $currentGroup = null;
            $currentStatus = null;

            $messages = $this->simpletestScriptLoadMessagesByTestIds([$testId]);

            foreach ($messages as $message) {
                if ($currentClass === null || $currentClass != $message->test_class) {
                    $currentClass = $message->test_class;
                    $io->comment($message->test_class);
                }

                if ($currentGroup === null || $currentGroup != $message->message_group) {
                    $currentGroup =  $message->message_group;
                }

                if ($currentStatus === null || $currentStatus != $message->status) {
                    $currentStatus =  $message->status;
                    if ($message->status == 'fail') {
                        $io->error($this->trans('commands.test.run.messages.group') . ':' . $message->message_group . ' ' . $this->trans('commands.test.run.messages.status') . ':' . $message->status);
                        $io->newLine();
                    } else {
                        $io->info($this->trans('commands.test.run.messages.group') . ':' . $message->message_group . ' ' . $this->trans('commands.test.run.messages.status') . ':' . $message->status);
                        $io->newLine();
                    }
                }

                $io->simple(
                    $this->trans('commands.test.run.messages.file') . ': ' . str_replace($this->appRoot, '', $message->file)
                );
                $io->simple(
                    $this->trans('commands.test.run.messages.method') . ': ' . $message->function
                );
                $io->simple(
                    $this->trans('commands.test.run.messages.line') . ': ' . $message->line
                );
                $io->simple(
                    $this->trans('commands.test.run.messages.message') . ': ' . $message->message
                );
                $io->newLine();
            }
            return null;
        }
    }

    protected function setEnvironment($url)
    {
        $base_url = 'http://';
        $port = '80';

        $parsed_url = parse_url($url);
        $host = $parsed_url['host'] . (isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '');
        $path = isset($parsed_url['path']) ? rtrim(rtrim($parsed_url['path']), '/') : '';
        $port = (isset($parsed_url['port']) ? $parsed_url['port'] : $port);
        if ($path == '/') {
            $path = '';
        }
        // If the passed URL schema is 'https' then setup the $_SERVER variables
        // properly so that testing will run under HTTPS.
        if ($parsed_url['scheme'] == 'https') {
            $_SERVER['HTTPS'] = 'on';
        }


        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $base_url = 'https://';
        }
        $base_url .= $host;
        if ($path !== '') {
            $base_url .= $path;
        }
        putenv('SIMPLETEST_BASE_URL=' . $base_url);
        $_SERVER['HTTP_HOST'] = $host;
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['SERVER_ADDR'] = '127.0.0.1';
        $_SERVER['SERVER_PORT'] = $port;
        $_SERVER['SERVER_SOFTWARE'] = null;
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['REQUEST_URI'] = $path . '/';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = $path . '/index.php';
        $_SERVER['SCRIPT_FILENAME'] = $path . '/index.php';
        $_SERVER['PHP_SELF'] = $path . '/index.php';
        $_SERVER['HTTP_USER_AGENT'] = 'Drupal Console';
    }

    /*
     * Get Simletests log after execution
     */

    protected function simpletestScriptLoadMessagesByTestIds($test_ids)
    {
        $results = [];

        foreach ($test_ids as $test_id) {
            $result = \Drupal::database()->query(
                "SELECT * FROM {simpletest} WHERE test_id = :test_id ORDER BY test_class, message_group, status", [
                ':test_id' => $test_id,
                ]
            )->fetchAll();
            if ($result) {
                $results = array_merge($results, $result);
            }
        }

        return $results;
    }
}
