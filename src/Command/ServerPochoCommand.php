<?php

/**
 * @file
 * Contains \Drupal\Console\Command\ServerCommand.
 */

namespace Drupal\Console\Command;

use Dflydev\ApacheMimeTypes\PhpRepository;
use Symfony\Component\Process\PhpProcess;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\HttpFoundation\Request;
use Yosymfony\HttpServer\HttpServer;
use Yosymfony\HttpServer\RequestHandler;
use Yosymfony\HttpServer\HttpKernelRequestHandler;
use Drupal\Core\DrupalKernel;


class ServerPochoCommand extends Command
{
    private $requestHandler;
    private $output;
    private $documentroot;
    private $port;
    private $host;
    private $defatultMimeType = 'text/html; charset=UTF-8';


    protected function configure()
    {
        $this
            ->setName('server:pocho')
            ->setDescription($this->trans('commands.server.description'))
            ->addOption(
                'host',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.site.install.options.host')
            )
            ->addOption(
                'port',
                '',
                InputOption::VALUE_OPTIONAL,
                $this->trans('commands.site.install.options.port')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $renderer = $this->getRenderHelper();
        $application = $this->getApplication();
        $drupal = $this->getDrupalHelper();
        $this->output = $output;

        $host = $input->getOption('host');
        $port = $input->getOption('port');

        if($host) {
            $this->host = $host;
        } else {
            $this->host = '127.0.0.1';
        }

        if($port) {
            $this->port = $port;
        } else {
            $this->port = '8081';
        }

        $this->documentroot = $drupal->getRoot();

        $httpKernel = new DrupalKernel('prod', $drupal->getAutoLoadClass());
        $options = array(
            'host' => $this->host,
            'port' => $this->port
        );

        // Wrap it with the RequestHandler.
       /* $GLOBALS['base_url'] = $this->host;
        $GLOBALS['base_path'] = '/';
        $GLOBALS['base_url'] = 'http://' . $this->host . ':' . $this->port;*/
        $handler = new HttpKernelRequestHandler($httpKernel, $options);
        //$drupalRequest = Request::createFromGlobals();
        //$httpKernel->preHandle($drupalRequest);
        //$handler = $httpKernel->handle($request);*/

        // Start the server using the RequestHandler.
        $server = new HttpServer($handler);
        $server->start();
    }

    private function logRequest(Request $request, $statusCode)
    {
        $date = new \Datetime();
        $data = sprintf('[%s] %s [%s] %s',
            $date->format('Y-m-d h:i:s'),
            $request->getClientIp(),
            $statusCode,
            $request->getPathInfo());
        if ($statusCode >= 400) {
            $data = '<error>'.$data.'</error>';
        }
        $this->output->writeln($data);
    }

    private function initialMessage()
    {
        $this->output->writeln('<comment>Drupal Console server running... press ctrl-c to stop</comment>');
        $this->output->writeln(sprintf(
            '<comment>Port: %s Host: %s Document root: %s</comment>',
            $this->port,
            $this->host,
            $this->documentroot));
    }

    private function resolvePath(Request $request)
    {
        if($request->getPathInfo() == '/') {
            $path = $this->documentroot . '/index.php';
        } else {
            $path = $this->documentroot . $request->getPathInfo();
        }

        if (is_dir($path)) {
            $path .= '/index.html';
        }
        return $path;
    }

    private function getResponseError($statusCode, $data)
    {
        return [
            'content' => $this->getError($statusCode, $data),
            'headers' => ['Content-Type' => 'text/html'],
            'status_code' => $statusCode,
        ];
    }

    private function getError($statusCode, $data)
    {
        switch ($statusCode) {
            case 404:
                $message = sprintf('Resource not found: %s', $data);
                break;
            case 500:
                $message = sprintf('Server exception: %s', $data);
                break;
            default:
                $message = $data;
        }
        return [
            'status_code' => $statusCode,
            'message' => $message,
        ];
    }

    private function getMimeTypeFile($path)
    {
        $mimetypeRepo = new PhpRepository();
        return $mimetypeRepo->findType(pathinfo($path, PATHINFO_EXTENSION)) ?: $this->defatultMimeType;
    }

    private function getExtension($path) {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    private function getResponseOk($content, $contentType)
    {
        return [
            'content' => $content,
            'headers' => ['Content-Type' => $contentType],
            'status_code' => 200,
        ];
    }
}
