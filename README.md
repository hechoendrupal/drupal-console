<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**  *generated with [DocToc](https://github.com/thlorenz/doctoc)*

- [Drupal Console](#drupal-console)
  - [Supported Drupal version](#supported-drupal-version)
  - [Drupal Console documentation](#drupal-console-documentation)
  - [Installing Drupal Console](#installing-drupal-console)
  - [Using Drupal Console](#using-drupal-console)
  - [Supporting organizations](#supporting-organizations)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

Drupal Console
=============================================

[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/hechoendrupal/DrupalConsole)
[![Build Status](https://travis-ci.org/hechoendrupal/DrupalConsole.svg?branch=master)](https://travis-ci.org/hechoendrupal/DrupalConsole)
[![Latest Stable Version](https://poser.pugx.org/drupal/console/v/stable.svg)](https://packagist.org/packages/drupal/console)
[![Latest Unstable Version](https://poser.pugx.org/drupal/console/v/unstable.svg)](https://packagist.org/packages/drupal/console)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/d0f089ff-a6e9-4ba4-b353-cb68173c7d90/mini.png)](https://insight.sensiolabs.com/projects/d0f089ff-a6e9-4ba4-b353-cb68173c7d90)

The Drupal Console is a suite of tools that you run on a command line interface (CLI)
to generate boilerplate code and interact with a Drupal 8 installation.

## Change Log
All notable changes to this project will be documented in the [releases page](https://github.com/hechoendrupal/DrupalConsole/releases) 

## Supported Drupal version
The Drupal 8 supported version is [Drupal 8.0.0](http://ftp.drupal.org/files/projects/drupal-8.0.0.tar.gz).

## Drupal Console documentation
You can read or download the Drupal Console documentation at [bit.ly/console-book](http://bit.ly/console-book).

## Drupal Console support
You can ask for support at Drupal Console gitter chat room [http://bit.ly/console-support](http://bit.ly/console-support).

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

# Show all available commands.
drupal list

# Copy configuration files.
drupal init [--override]

# Generate a module.
drupal generate:module
```

## Getting the project to contribute

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

## Supporting organizations
[![FFW](https://www.drupal.org/files/ffw-logo.png)](https://ffwagency.com)  
[![Indava](https://www.drupal.org/files/indava-logo.png)](http://www.indava.com/)  
[![Anexus](https://www.drupal.org/files/anexus-logo.png)](http://www.anexusit.com/)
