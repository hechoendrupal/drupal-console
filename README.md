<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->


## [Sunsetting the project](https://github.com/hechoendrupal/drupal-console/issues/4350)

---

Drupal Console became essential for thousands of developers worldwide with over 15 million downloads, support for 19 languages, and 200+ commands. It significantly improved Drupal 8+ development workflows and reduced project delivery times. 

There is no need to maintain the project any longer, and that’s a good thing. As Drupal 8 matured, the ecosystem evolved with it. Drush embraced many of the interactive features that once made Drupal Console stand out, while also adopting the modern PHP and Symfony components that defined the new era of Drupal. 

The goal was never to compete, it was to modernize the CLI in Drupal, and we can say: mission accomplished. So today, there’s no need to maintain two different tools because Drush evolved, and picked up the torch. The mission was a success.

![Image](https://github.com/user-attachments/assets/27bae7b1-be7c-4a04-abf1-10d9837c1a33)


None of this could have been possible without the incredible support of the community. It’s amazing how a project that started as a Drupal 8 learning exercise grew to the point to be considered for the Drupal community a must-have tool to accelerate Drupal 8 development.

Thank you all for using the project, for attending talks at events, for providing feedback, creating issues, and sending pull requests, for spreading the word and love about the project sending a tweet, writing a blog post or recording a video, and very special thanks to all of the awesome contributors.

So the project is sunsetting, not because it failed, but because it succeeded.

--- 

**Table of Contents**  *generated with [DocToc](https://github.com/thlorenz/doctoc)*

  - [Drupal Console](#drupal-console)
  - [Required PHP version](#required-php-version)
  - [Drupal Console documentation](#documentation)
  - [Download Drupal Console](#download)
  - [Run Drupal Console](#run)
  - [Contributors](#contributors)
  - [Supporting organizations](#supporting-organizations)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

Drupal Console
=============================================

[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/hechoendrupal/DrupalConsole)
[![Build Status](https://travis-ci.org/hechoendrupal/drupal-console.svg?branch=master)](https://travis-ci.org/hechoendrupal/drupal-console)
[![Latest Stable Version](https://poser.pugx.org/drupal/console/v/stable.svg)](https://packagist.org/packages/drupal/console)
[![Latest Unstable Version](https://poser.pugx.org/drupal/console/v/unstable.svg)](https://packagist.org/packages/drupal/console)
[![Software License](https://img.shields.io/badge/license-GPL%202.0+-blue.svg)](https://packagist.org/packages/drupal/console)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/d0f089ff-a6e9-4ba4-b353-cb68173c7d90/mini.png)](https://insight.sensiolabs.com/projects/d0f089ff-a6e9-4ba4-b353-cb68173c7d90)

The Drupal CLI. A tool to generate boilerplate code, interact with and debug Drupal.

## Latest Version
Details of the latest version can be found on the Drupal Console project page under https://drupalconsole.com/.

## Releases Page
All notable changes to this project will be documented in the [releases page](https://github.com/hechoendrupal/DrupalConsole/releases)

## Documentation
The most up-to-date documentation can be found at [https://drupalconsole.com/docs/](https://drupalconsole.com/docs/).

More information about using this project at the [official documentation](https://drupalconsole.com/docs/en/using/project).

## Required PHP Version
PHP 5.5.9 or higher is required to use the Drupal Console application.

## Download 

[Install Drupal Console Using Composer](https://drupalconsole.com/docs/en/getting/composer)

[Install Drupal Console Launcher](https://drupalconsole.com/docs/en/getting/launcher)

[Installing Drupal Console on Windows](https://drupalconsole.com/docs/en/getting/windows)

## Run
Using the DrupalConsole Launcher
```
drupal
``` 

We highly recommend you to install the global executable, but if is not installed, you can run Drupal Console depending on your installation by executing:

```
vendor/bin/drupal
# or
vendor/drupal/console/bin/drupal
# or
bin/drupal
```

## Drupal Console Support
You can ask for support at Drupal Console gitter chat room [http://bit.ly/console-support](http://bit.ly/console-support).

## Contribute to Drupal Console
* [Getting the project](https://drupalconsole.com/docs/en/getting/project)
* [Using the project](https://drupalconsole.com/docs/en/using/project)

## Enabling Autocomplete
```
# You can enable autocomplete by executing
drupal init

# Bash: Bash support depends on the http://bash-completion.alioth.debian.org/
# project which can be installed with your package manager of choice. Then add
# this line to your shell configuration file.
source "$HOME/.console/console.rc" 2>/dev/null

# Zsh: Add this line to your shell configuration file.
source "$HOME/.console/console.rc" 2>/dev/null

# Fish: Create a symbolic link
ln -s ~/.console/drupal.fish ~/.config/fish/completions/drupal.fish
```

## Contributors

[Full list of contributors](https://drupalconsole.com/contributors)

## Supporting Organizations

[All supporting organizations](https://drupalconsole.com/supporting-organizations)

> Drupal is a registered trademark of Dries Buytaert.
