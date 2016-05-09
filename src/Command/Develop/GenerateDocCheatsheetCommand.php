<?php

/**
 * @file
 * Contains \Drupal\Console\Command\Develop\GenerateDocCheatsheetCommand.
 *
 * @TODO: use twig
 */

namespace Drupal\Console\Command\Develop;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Drupal\Console\Command\ContainerAwareCommand;
use Drupal\Console\Style\DrupalStyle;
use Knp\Snappy\Pdf;

class GenerateDocCheatsheetCommand extends ContainerAwareCommand
{
    private $singleCommands = [
      'about',
      'chain',
      'help',
      'list',
      'server'
    ];

    //exclude: yaml, translation
    private $orderCommands = [
      'cache',
      'chain',
      'config',
      'database',
      'create',
      'cron',
      'image',
      'container',
      'locale',
      'migrate',
      'module',
      'multisite',
      'rest',
      'settings',
      'views',
      'router',
      'state',
      'user',
      'site',
      'update',
      'theme'

    ];

    private $logoUrl = 'http://drupalconsole.com/themes/custom/drupalconsole/assets/src/images/drupal-console.png';

    private $wkhtmltopdfPath = "/usr/bin/wkhtmltopdf";

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('generate:doc:cheatsheet')
            ->setDescription($this->trans('commands.generate.doc.cheatsheet.description'))
            ->addOption(
                'path',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.doc.cheatsheet.options.path')
            )
            ->addOption(
                'wkhtmltopdf',
                null,
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.generate.doc.cheatsheet.options.wkhtmltopdf')
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

        // $wkhtmltopdfPath is overwritable by command option

        if ($input->getOption('wkhtmltopdf')) {
            $this->wkhtmltopdfPath = $input->getOption('wkhtmltopdf');
        }

        $application = $this->getApplication();
        $command_list = [];

        foreach ($this->singleCommands as $single_command) {
            $command = $application->find($single_command);
            $command_list['none'][] = [
                'name' => $command->getName(),
                'description' => $command->getDescription(),
            ];
        }

        $namespaces = $application->getNamespaces();
        sort($namespaces);

        $namespaces = array_filter(
            $namespaces, function ($item) {
                return (strpos($item, ':')<=0);
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
                if ($command->getModule()=='Console') {
                    $command_list[$namespace][] = [
                        'name' => $command->getName(),
                        'description' => $command->getDescription(),
                    ];
                }
            }
        }

        if (!empty($command_list)) {
            $this->prepareHtml($command_list, $path, $io);
        }
    }


    /**
     * Generates (programatically, not with twig) the HTML to convert to PDF
     *
     * @param array  $array_content
     * @param string $path
     */
    protected function prepareHtml($array_content, $path, $io)
    {
        $str  = '<meta charset="UTF-8" />';
        $str .= "<center><div style='font-size: 12px;'>Drupal Console cheatsheet</div></center>";

        // 1st page
        foreach ($this->orderCommands as $command) {
            $str .= $this->doTable($command,  $array_content[$command]);
        }

        // 2nd page
        $str .= "<br/><br/><table style='width:99%;page-break-before:always;padding-top:10%'><tr><td><img src='".
              $this->logoUrl ."' width='150px' style='float:left'/></td>";

        $str .= "<td style='vertical-align: bottom;'><h1>DrupalConsole Cheatsheet</h1></td></tr></table><br/><br/>";

        $str .= $this->doTable("generate",  $array_content["generate"]);
        $str .= $this->doTable("miscelaneous",  $array_content["none"]);

        $this->doPdf($str, $path, $io);
    }


    /**
     * Generates the pdf with Snappy
     *
     * @param string $content
     * @param string $path
     *
     * @return string
     */
    protected function doPdf($content, $path, $io)
    {
        $snappy = new Pdf();
        //@TODO: catch exception if binary path doesn't exist!
        $snappy->setBinary($this->wkhtmltopdfPath);
        $snappy->setOption('orientation', "Landscape");
        $snappy->generateFromHtml($content, "/" .$path . 'dc-cheatsheet.pdf');
        $io->success("cheatsheet generated at /" .$path ."/dc-cheatsheet.pdf");

        // command execution ends here
    }

    /**
   * Encloses text in <td> tags
   *
   * @param string $str
   *
   * @return string
   */
    public function td($str, $mode = null)
    {
        if ("header" == $mode) {
            return "<td colspan='2' style='background-color:whitesmoke;font-size: 12px;'><b>" . strtoupper($str) . "</b></td>";
        } else {
            if ("body" == $mode) {
                return "<td style='font-size: 11px;width=35%'><i>". $str. "</i></td>";
            } else {
                return "<td>" . $str . "</td>";
            }
        }
    }

    /**
   * Encloses text in <tr> tags
   *
   * @param string $str
   * @param array  $element
   *
   * @return string
   */
    public function tr($str)
    {
        return "<tr>" . $str . "</tr>";
    }

    /**
   * Encloses text in <table> tag
   *
   * @param string $key_element - header
   * @param array  $element     - command, description
   *
   * @return string
   */
    public function doTable($key_element, $element)
    {
        $str = "<table cellspacing='0' border='0' style='float:left;width:49%;'>";
        $str .= $this->td($key_element, "header");

        foreach ($element as $section) {
            $str .= $this->tr($this->td($section["name"], "body") . $this->td($section["description"], "body"));
        }

        return $str . "</table>\n\r";
    }
}
