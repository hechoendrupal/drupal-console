<?php

namespace Drupal\AppConsole\Command;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Egulias\ListenersDebug\Listener\ListenerFetcher;
use Egulias\ListenersDebug\Listener\ListenerFilter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Class ListenersDebugCommand
 * @author Eduardo Gulias Davis <me@egulias.com>
 */
class ListenersDebugCommand extends ContainerAwareCommand
{
    /**
     * {@inherit}
     */
    protected function configure()
    {
        $this->setDefinition(
            array(
                new InputArgument('name', InputArgument::OPTIONAL, 'A (service) listener name (foo) or search (foo*)'),
                new InputOption(
                    'event',
                    null,
                    InputOption::VALUE_REQUIRED,
                    'Provide an event name (foo.bar) to filter'
                ),
                new InputOption(
                    'order-desc',
                    null,
                    InputOption::VALUE_NONE,
                    'Order listeners by descending priority (default\'s ascending) - ' .
                    '(Only applies when used with --event option)'
                ),
                new InputOption('subscribers', null, InputOption::VALUE_NONE, 'Use to show *only* event subscribers'),
                new InputOption('listeners', null, InputOption::VALUE_NONE, 'Use to show *only* event listeners'),
                new InputOption(
                    'show-private',
                    null,
                    InputOption::VALUE_NONE,
                    'Use to show public *and* private services listeners'
                ),
            )
        )
            ->setName('container:debug:listeners')
            ->setDescription('Displays current services defined as listeners for an application')
            ->setHelp(
                <<<EOF
    The <info>container:debug:listeners</info> command displays all configured <comment>public</comment>
services defined as listeners:

  <info>container:debug:listeners</info>

EOF
            );
    }

    /**
     * {@inherit}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        $options = array(
            'show-private' => $input->getOption('show-private'),
            'event'        => $input->getOption('event'),
            'order-desc'        => $input->getOption('order-desc'),
            'show-listeners' => $input->getOption('listeners'),
            'show-subscribers' => $input->getOption('subscribers'),
        );

        if ($name) {
            $this->outputListener($output, $name, $options);
        } else {
            $this->outputListeners($output, $options);
        }
    }

    /**
     * outputListeners
     *
     * @param OutputInterface $output       Output
     * @param array           $options      array of options from the console
     *
     */
    protected function outputListeners(OutputInterface $output, $options = array())
    {
        $fetcher = new ListenerFetcher($this->getContainerBuilder());
        $filter = new ListenerFilter();
        $listeners = $fetcher->fetchListeners($options['show-private']);

        if ($options['event']) {
            $listeners = $filter->filterByEvent($options['event'], $listeners, $options['order-desc']);
        }

        if ($options['show-listeners']) {
            $listeners = $filter->fetchListeners($listeners);
        }

        if ($options['show-subscribers']) {
            $listeners = $filter->fetchSubscribers($listeners);
        }

        $label = '<comment>Public</comment> (services) listeners';
        if ($options['show-private']) {
            $label = '<comment>Public</comment> and <comment>private</comment> (services) listeners';
        }

        $output->writeln($this->getHelper('formatter')->formatSection('container', $label));

        $table = $this->getHelperSet()->get('table');
        $table->setHeaders(array('Name', 'Event', 'Method', 'Priority', 'Type', 'Class Name'));
        $table->setCellRowFormat('<fg=white>%s</fg=white>');
        $table->setRows($listeners);
        $table->render($output);
    }

    /**
     * Renders detailed service information about one listener
     */
    protected function outputListener(OutputInterface $output, $serviceId)
    {
        $fetcher = new ListenerFetcher($this->getContainerBuilder());
        $definition = $fetcher->fetchListener($serviceId);

        $label = sprintf('Information for listener <info>%s</info>', $serviceId);
        $output->writeln($this->getHelper('formatter')->formatSection('container', $label));
        $output->writeln('');

        if ($definition instanceof Alias) {
            $output->writeln(sprintf('This service is an alias for the service <info>%s</info>', (string) $definition));
            return;
        }

        $output->writeln(sprintf('<comment>Listener Id</comment>   %s', $serviceId));
        $output->writeln(sprintf('<comment>Class</comment>         %s', $definition->getClass()));

        if ($definition instanceof Definition) {
            return;
        }

        $type = ($fetcher->isSubscriber($definition)) ? 'subscriber' : 'listener';
        $output->writeln(sprintf('<comment>Type</comment>         %s', $type));
        $output->writeln(sprintf('<comment>Listens to</comment>', ''));
        $events = array();

        $tags = $definition->getTags();
        foreach ($tags as $tag => $details) {
            if (preg_match(self::SUBSCRIBER_PATTERN, $tag)) {
                $subscribed = $this->getEventSubscriberInformation($definition->getClass());
                foreach ($subscribed as $name => $current) {
                    //Exception when event only has the method name
                    if (!is_array($current)) {
                        $current = array($current);
                    } elseif (is_array($current[0])) {
                        $current = $current[0];
                    }

                    $event['name'] = $name;
                    $event['method'] = $current[0];
                    $event['priority'] = (isset($current[1])) ? $current[1] : 0;
                }
            } elseif (preg_match(self::LISTENER_PATTERN, $tag)) {
                foreach ($details as $current) {
                    $event['name'] = $current['event'];
                    $event['method'] = (isset($current['method'])) ? $current['method'] : $current['event'];
                    $event['priority'] = isset($current['priority']) ? $current['priority'] : 0;
                }
            }
        }

        foreach ($events as $event) {
            $output->writeln(sprintf('<comment>  -Event</comment>         %s', $event['name']));
            $output->writeln(sprintf('<comment>  -Method</comment>        %s', $event['method']));
            $output->writeln(sprintf('<comment>  -Priority</comment>      %s', $event['priority']));
            $output->writeln(sprintf('<comment>  -----------------------------------------</comment>'));
        }

        $tags = $tags ? implode(', ', array_keys($tags)) : '-';
        $output->writeln(sprintf('<comment>Tags</comment>         %s', $tags));
        $public = $definition->isPublic() ? 'yes' : 'no';
        $output->writeln(sprintf('<comment>Public</comment>       %s', $public));
    }

    private function getContainerBuilder()
    {
        $parameters  = $this->getContainer()->getParameterBag()->all();
        return new ContainerBuilder(new ParameterBag($parameters));
    }
}