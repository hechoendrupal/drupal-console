<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->

- [Drupal Console](#drupal-console)
  - [Supported version](#supported-version)
  - [Documentation](#documentation)
  - [Installation](#installation)
  - [Usage](#usage)
  - [Supporting organizations](#supporting-organizations)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

Drupal Console
=============================================

[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/hechoendrupal/DrupalAppConsole)
[![Build Status](https://travis-ci.org/hechoendrupal/DrupalAppConsole.svg?branch=master)](https://travis-ci.org/hechoendrupal/DrupalAppConsole)
[![Latest Stable Version](https://poser.pugx.org/drupal/console/v/stable.svg)](https://packagist.org/packages/drupal/console)
[![Latest Unstable Version](https://poser.pugx.org/drupal/console/v/unstable.svg)](https://packagist.org/packages/drupal/console) 
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/d0f089ff-a6e9-4ba4-b353-cb68173c7d90/mini.png)](https://insight.sensiolabs.com/projects/d0f089ff-a6e9-4ba4-b353-cb68173c7d90)

The Drupal Console is an effort to bring The Symfony Console Component to Drupal 8.

This project takes the Symfony Console component and makes it available on Drupal to automatically generate most of the new Drupal 8 module requirements.  

This tool does not only generates the module code, also helps you interact with your Drupal installation.  

## Supported version

[Drupal 8.0.0-beta6](http://ftp.drupal.org/files/projects/drupal-8.0.0-beta6.tar.gz)

## Documentation

You can read online of Downlad the Documentation of Drupal Console at [gitbooks.io](http://hechoendrupal.gitbooks.io/drupal-console/)

## Installation
```
// Run this in your terminal to get the latest Console version:
$ curl -LSs http://drupalconsole.com/installer | php

// Or if you don't have curl:
$ php -r "readfile('http://drupalconsole.com/installer');" | php

// Accessing console from anywhere on your system:
$ mv console.phar /usr/local/bin/drupal

// Use the project
$ drupal generate:module
```

## Usage

![image](http://drupalconsole.com/assets/img/console-global.gif)

## Supporting organizations
[![Blink Reaction](https://www.drupal.org/files/blink-reaction-logo.png)](http://www.blinkreaction.com/)
[![Indava](https://www.drupal.org/files/indava-logo.png)](http://www.indava.com/)
