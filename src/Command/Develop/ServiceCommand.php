<?php

namespace Drupal\Console\Command\Develop;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Drupal\Console\Core\Command\Shared\CommandTrait;
use Drupal\Console\Core\Style\DrupalStyle;


/**
 * Class TYPDBServiceCommand.
 *
 */
class ServiceCommand extends Command
{

    use CommandTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure() {
        $this->setName('devel:service')
            ->setDescription('Service at your command line')->addArgument(
                'service',
                InputArgument::REQUIRED, 'Service Name',
                null
            )->addArgument(
                'method',
                InputArgument::OPTIONAL, 'Method Name (default is to list functions in the service)',
                '_list'
            )->addArgument(
                'args',
                InputArgument::OPTIONAL, 'Arguments in JSON/CSV format (array)',
                null
            );
    }

    /**
     * {@inheritdoc}
     */


    protected function execute(InputInterface $input, OutputInterface $output) {
        ini_set('memory_limit', '4096M');
        set_time_limit(0);
        $io = new DrupalStyle($input, $output);
        $service = $input->getArgument("service");
        $method = $input->getArgument("method");
        $args = $input->getArgument("args");
        $sv = \Drupal::service($service);
        $reqsymbol = ''; //$reqsymbol = '*';
        if ($method == '_list') {

            $mlist = get_class_methods($sv);
            $mset = array();
            foreach ($mlist as $m) {
                $reflection = new \ReflectionMethod($sv, $m);
                $numreq = $reflection->getNumberOfRequiredParameters();
                $params = $reflection->getParameters();
                $p = array();

                for ($i = 0; $i < count($params) ; $i++) {
                    if ($params[$i]->isDefaultValueAvailable()) {
                        $def = $params[$i]->getDefaultValue();
                        $def = "=".str_replace(array("\n","array ("), array("", "array("), var_export($def,true));
                    } else {
                        $def = '';
                    }
                    if ($params[$i]->isPassedByReference()) $pref = '<fg=magenta>&</>';
                    else $pref = '';
                    if ($numreq > $i) $p[] = $reqsymbol.$pref.'<fg=red>'.'$'.$params[$i]->getName().'</>'.$def;
                    else $p[] = $pref.'<fg=red>$'.$params[$i]->getName().'</>'.$def;
                }
                if ($reflection->isPublic()) {
                    $mset[] = array('<fg=yellow>'.$service.'</>', '<fg=blue>'.get_class($sv).'</>', '<fg=yellow>'.$m.'</>', implode(",", $p));
                }
            }
            $io->table(array('Service', 'Class', 'Public Method', 'Parameters'), $mset);

        } else {
            $xargs = json_decode($args, TRUE);


            if (!is_array($xargs)) $xargs = explode(",", $args);
            if (!method_exists($sv, $method)) {
                $io->error("$method doesn't exist in $service");
                exit;
            }
            $io->info("<fg=cyan>###[<fg=yellow>COMMAND</>]###</>");
            $io->info("\\Drupal::service('<fg=blue>$service</>')-><fg=blue>$method</>(\n<fg=red>".json_encode($xargs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE )."</>)");
            $ret = call_user_func_array(array($sv,$method), $xargs);
            $io->info("<fg=cyan>###[<fg=yellow>RETURN</>]###</>");
            $io->info(json_encode($ret, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));


        }
    }
}
