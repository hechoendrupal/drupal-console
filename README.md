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
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/d0f089ff-a6e9-4ba4-b353-cb68173c7d90/mini.png)](https://insight.sensiolabs.com/projects/d0f089ff-a6e9-4ba4-b353-cb68173c7d90)

The Drupal Console is a suite of tools that you run on a command line interface (CLI)
to generate boilerplate code and interact with a Drupal 8 installation.

## Change Log
All notable changes to this project will be documented in the [releases page](https://github.com/hechoendrupal/DrupalConsole/releases) 

## Supported Drupal version
The Drupal 8 supported version is [Drupal 8 Beta 15](http://ftp.drupal.org/files/projects/drupal-8.0.0-beta15.tar.gz).

## Drupal Console documentation
You can read or download the Drupal Console documentation at [bit.ly/console-book](http://bit.ly/console-book).

## Drupal Console support
You can ask for support at Drupal Console gitter chat room [http://bit.ly/console-support](http://bit.ly/console-support).

## Installing Drupal Console
```
# Run this in your terminal to get the latest Console version:
curl -LSs http://drupalconsole.com/installer | php

# Or if you don't have curl:
php -r "readfile('http://drupalconsole.com/installer');" | php

# You can place this file anywhere you wish.
# If you put it in your PATH, you can access it globally.
# For example: move console.phar and rename it, 'drupal':
mv console.phar /usr/local/bin/drupal

# Show all available Drupal Console commands.
drupal

# Generate a module.
drupal generate:module
```

## Using Drupal Console
![image](http://drupalconsole.com/assets/img/console-global.gif)

## Supporting organizations
[![FFW](https://www.drupal.org/files/ffw-logo.png)](https://ffwagency.com)  
[![Indava](https://www.drupal.org/files/indava-logo.png)](http://www.indava.com/)  
[![Anexus](https://www.drupal.org/files/anexus-logo.png)](http://www.anexusit.com/)
