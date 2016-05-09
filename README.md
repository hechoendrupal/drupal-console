<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**  *generated with [DocToc](https://github.com/thlorenz/doctoc)*

- [Drupal Console](#drupal-console)
  - [Required PHP version](#required-php-version)
  - [Supported Drupal version](#supported-drupal-version)
  - [Drupal Console documentation](#documentation)
  - [Installing Drupal Console](#installing-drupal-console)
  - [Running Drupal Console](#running-drupal-console)
  - [Supporting organizations](#supporting-organizations)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

Drupal Console
=============================================

[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/hechoendrupal/DrupalConsole)
[![Build Status](https://travis-ci.org/hechoendrupal/DrupalConsole.svg?branch=master)](https://travis-ci.org/hechoendrupal/DrupalConsole)
[![Latest Stable Version](https://poser.pugx.org/drupal/console/v/stable.svg)](https://packagist.org/packages/drupal/console)
[![Latest Unstable Version](https://poser.pugx.org/drupal/console/v/unstable.svg)](https://packagist.org/packages/drupal/console)
[![Software License](https://img.shields.io/badge/license-GPL%202.0+-blue.svg)](https://packagist.org/packages/drupal/console)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/d0f089ff-a6e9-4ba4-b353-cb68173c7d90/mini.png)](https://insight.sensiolabs.com/projects/d0f089ff-a6e9-4ba4-b353-cb68173c7d90)

The Drupal Console is a CLI tool to generate boilerplate code, interact and debug Drupal 8.

## Latest Version
Details of the latest version can be found on the Drupal Console project page under https://drupalconsole.com/.

## Releases Page
All notable changes to this project will be documented in the [releases page](https://github.com/hechoendrupal/DrupalConsole/releases)

## Documentation
The most up-to-date documentation can be found at [http://docs.drupalconsole.com/](http://docs.drupalconsole.com/).

More information about using this project at the [official documentation](http://docs.drupalconsole.com/en/using/project.html).

## Required PHP Version
PHP 5.5.9 or higher is required to use the Drupal Console application.

## Supported Drupal Version
The Drupal 8 supported version is [Drupal 8.0.x](https://www.drupal.org/node/3060/release).

## Installing Drupal Console
```
# Run this in your terminal to get the latest Console version:
curl https://drupalconsole.com/installer -L -o drupal.phar

# Or if you don't have curl:
php -r "readfile('https://drupalconsole.com/installer');" > drupal.phar

# Accessing from anywhere on your system:
mv drupal.phar /usr/local/bin/drupal

# Apply executable permissions on the downloaded file:
chmod +x /usr/local/bin/drupal

# Copy configuration files.
drupal init --override

# Check and validate system requirements
drupal check
```

## Running Drupal Console
```
# Download, install and serve Drupal 8:
drupal chain --file=~/.console/chain/quick-start.yml

# Create a new Drupal 8 project:
drupal site:new drupal8.dev --latest

# Lists all available commands:
drupal list

# Update to the latest version.
drupal self-update
```

## Drupal Console Support
You can ask for support at Drupal Console gitter chat room [http://bit.ly/console-support](http://bit.ly/console-support).

## Getting The Project To Contribute

### Fork
Fork your own copy of the [Console](https://github.com/hechoendrupal/DrupalConsole/fork) repository to your account

### Clone
Get a copy of your recently cloned version of console in your machine.
```
$ git clone git@github.com:[your-git-user-here]/DrupalConsole.git
```
### Install dependencies
Now that you have cloned the project, you need to download dependencies via Composer.

```
$ cd /path/to/DrupalConsole
$ composer install
```

### Running the project
After using Composer to download dependencies, you can run the project by executing:

```
$ bin/drupal
```

### Create a symbolic link

You can run this command to easily access the Drupal Console from anywhere on your system:

```
$ sudo ln -s /path/to/DrupalConsole/bin/drupal /usr/local/bin/drupal
```

**NOTE:** The name `drupal` is just an alias you can name it anything you like.

More information about how to contribute with this project at the [official documentation](http://docs.drupalconsole.com/en/contributing/new-features.html).

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

## Supporting Organizations
[![FFW](https://www.drupal.org/files/ffw-logo.png)](https://ffwagency.com)

[![Anexus](https://www.drupal.org/files/anexus-logo.png)](http://www.anexusit.com/)

[![Indava](https://www.drupal.org/files/indava-logo.png)](http://www.indava.com/)

> Drupal is a registered trademark of Dries Buytaert.
