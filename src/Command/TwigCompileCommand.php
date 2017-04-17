<?php

/**
 * @file
 * Contains \Drupal\Console\Command\TwigCompileCommand.
 */

namespace Drupal\Console\Command;


use Drupal\Console\Style\DrupalStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TwigCompileCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('twig:compile')
            ->setDescription($this->trans('commands.twig.compile.description'));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new DrupalStyle($input, $output);

        // Wipe the Twig PHP Storage cache.
        $this->getSettings()->get('php_storage');
        $this->getPhpStorage('twig')->deleteAll();


        // Load the theme engine and recompile
        $this->getDrupalHelper()->loadLegacyFile('/core/themes/engines/twig/twig.engine');
        $config = $this->getConfigFactory()->get('system.theme');
        $this->compileRegistry($config->get('default'));
        $this->compileRegistry($config->get('admin'));

        $io->success($this->trans('commands.twig.compile.messages.completed'));
    }

    /**
     * Compiles a theme registry's twig templates.
     *
     * @param $name
     *   The theme name.
     *
     * @throws \Exception
     */
    protected function compileRegistry($name)
    {
        require_once $this->getDrupalHelper()->getRoot() . "/themes/engines/twig/twig.engine";

        /** @var \Drupal\Core\Theme\ThemeManager $theme_manager */
        $theme_manager = $this->getService('theme.manager');

        $compile_theme = $this->getService('theme.initialization')->initTheme($name);
        $theme_manager->setActiveTheme($compile_theme);
        $active_theme = $theme_manager->getActiveTheme();

        /** @var \Drupal\Core\Theme\Registry $registry */
        $registry = $this->getService('theme.registry');
        foreach ($registry->get() as $theme_hook => $hook_data) {
            if (isset($hook_data['function'])) {
                continue;
            }
            $render_function = 'twig_render_template';
            $extension = '.html.twig';

            // The theme engine may use a different extension and a different
            // renderer.
            $theme_engine = $active_theme->getEngine();
            if (isset($theme_engine)) {
                if ($hook_data['type'] != 'module') {
                    if (function_exists($theme_engine . '_render_template')) {
                        $render_function = $theme_engine . '_render_template';
                    }
                    $extension_function = $theme_engine . '_extension';
                    if (function_exists($extension_function)) {
                        $extension = $extension_function();
                    }
                }
            }
            // Render the output using the template file.
            $template_file = $hook_data['template'] . $extension;
            if (isset($hook_data['path'])) {
                $template_file = $hook_data['path'] . '/' . $template_file;
            }
            $output = $render_function($template_file, []);
        }
    }

    /**
     * Instantiates a storage for generated PHP code.
     *
     * @see \Drupal\Core\PhpStorage\PhpStorageFactory::get
     *
     * @param string $name
     *   The name for which the storage should be returned. Defaults to 'default'
     *   The name is also used as the storage bin if one is not specified in the
     *   configuration.
     *
     * @return \Drupal\Component\PhpStorage\PhpStorageInterface
     *   An instantiated storage for the specified name.
     */
    protected function getPhpStorage($name)
    {
        $overrides = $this->getSettings()->get('php_storage');
        if (isset($overrides[$name])) {
            $configuration = $overrides[$name];
        }
        elseif (isset($overrides['default'])) {
            $configuration = $overrides['default'];
        }
        else {
            $configuration = array(
                'class' => 'Drupal\Component\PhpStorage\MTimeProtectedFileStorage',
                'secret' => $this->getSettings()->getHashSalt(),
            );
        }
        $class = isset($configuration['class']) ? $configuration['class'] : 'Drupal\Component\PhpStorage\MTimeProtectedFileStorage';
        if (!isset($configuration['bin'])) {
            $configuration['bin'] = $name;
        }
        if (!isset($configuration['directory'])) {
            $configuration['directory'] = PublicStream::basePath() . '/php';
        }
        return new $class($configuration);
    }
}
