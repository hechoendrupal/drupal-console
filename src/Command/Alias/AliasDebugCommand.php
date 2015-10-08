<?php

namespace Drupal\Console\Command\Alias;

use Drupal\Console\Command\Command;
use Drupal\Console\Console\Application;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class AliasDebugCommand extends Command
{
    public function configure()
    {
        $this
            ->setName('alias:debug')
            ->setDescription($this->trans('commands.remote.debug.description'))
            ->setHelp($this->trans('commands.remote.debug.help'));
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
 * @var Application $app 
*/
        $app = $this->getApplication();
        $homeDir = $app->getConfig()->getUserHomeDir();

        $remoteDir = $homeDir . '/.console/alias';
        $finder = new Finder();
        $finder->in($remoteDir);
        $finder->name("*.yml");

        $table = new Table($output);

        /**
 * @var \Symfony\Component\Finder\SplFileInfo $site 
*/
        foreach ($finder as $site) {
            $environments  = $app->getConfig()->readYamlFile($remoteDir . '/' . $site->getFilename());
            $site = explode(".", $site->getFilename())[0];

            foreach ($environments as $env => $config) {
                $table->addRow(
                    [
                    $site . '.' . $env,
                    array_key_exists('host', $config) ? $config['host'] : 'local',
                    $config['drupal']
                    ]
                );
            }
        }

        $table->setStyle("compact");
        $table->render();
    }
}
