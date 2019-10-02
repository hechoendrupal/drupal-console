<?php

namespace Drupal\Console\Command\Shared;

/**
 * Class ThemeTrait
 *
 * @package Drupal\Console\Command
 */
trait ThemeTrait
{
    /**
     * Ask the user to choose a theme.
     * 
     * @throws \Exception
     *   When no modules are found.
     *
     * @return string
     */
    public function themeQuestion()
    {
        $themes = $this->extensionManager->discoverThemes()
            ->showInstalled()
            ->showNoCore()
            ->getList(true);

        if (empty($themes)) {
            throw new \Exception('No themes installed available');
        }

        $theme = $this->getIo()->choiceNoList(
            $this->trans('commands.common.questions.theme'),
            $themes
        );

        return $theme;
    }

    /**
     * Get theme name from user.
     *
     * @return mixed|string
     *   Theme name.

     */
    public function getThemeOption()
    {
        $input = $this->getIo()->getInput();
        $theme = $input->getOption('theme');
        if (!$theme) {
            // @see Drupal\Console\Command\Shared\ThemeTrait::themeQuestion
            $theme = $this->themeQuestion();
            $input->setOption('theme', $theme);
        } else {
            $this->validatetheme($theme);
        }

        return $theme;
    }

    /**
     * Validate theme.
     *
     * @param string $theme
     *   Theme name.
     * @return string
     *   Theme name.
     *
     * @throws \Exception
     *   When theme is not found.
     */
    public function validateTheme($theme)
    {
        $missing_themes = $this->validator->getMissingThemes([$theme]);
        if ($missing_themes) {
            throw new \Exception(
                sprintf(
                    $this->trans(
                        'commands.common.messages.theme'
                    ),
                    $theme
                )
            );
        }
        return $theme;
    }

}
