<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->

- [Drupal Console](#drupal-console)
  - [Supported version](#supported-version)
  - [Documentation](#documentation)
  - [Installation](#installation)
  - [Usage](#usage)
  - [Supporting organizations](#supporting-organizations)
  - [How to Contribute](#how-to-contribute)
    - [Fork](#fork)
    - [Clone](#clone)
    - [Install dependencies](#install-dependencies)
    - [Global reference to Console Dev](#global-reference-to-console-dev)
    - [Create a custom Phar](#create-a-custom-phar)
      - [Install Box](#install-box)
      - [Create a custom Phar](#create-a-custom-phar-1)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

Drupal Console
=============================================
[![Build Status](https://travis-ci.org/hechoendrupal/DrupalAppConsole.svg?branch=master)](https://travis-ci.org/hechoendrupal/DrupalAppConsole)
[![Latest Stable Version](https://poser.pugx.org/drupal/console/v/stable.svg)](https://packagist.org/packages/drupal/console) [![Latest Unstable Version](https://poser.pugx.org/drupal/console/v/unstable.svg)](https://packagist.org/packages/drupal/console) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/d0f089ff-a6e9-4ba4-b353-cb68173c7d90/mini.png)](https://insight.sensiolabs.com/projects/d0f089ff-a6e9-4ba4-b353-cb68173c7d90)

## Supported version

[Drupal 8.0.0-beta4](http://ftp.drupal.org/files/projects/drupal-8.0.0-beta4.tar.gz)

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

## How to Contribute

If you want to contribute to Drupal Console, check the following instructions to setup your environment.

### Fork

Using your github account [fork](https://github.com/enzolutions/DrupalAppConsole/fork) this project to get your own version of Drupal Console.

### Clone

Get a copy of your recently clone version of console in your machine

```
$ git clone git@github.com:[your-git-user-here]/DrupalAppConsole.git ~/DrupalAppConsole

$ cd ~/DrupalAppConsole
```

### Install dependencies

Now you have to download the depencencies via componser with the following command

```
$ composer update --no-dev
```

If you don't have composer in your system, check how to install composer [here](https://getcomposer.org/download/).

### Global reference to Console Dev

Now to enable to have Drupal Console stable release and you dev version we will create symbolic link to our dev version with the following command

```
$ sudo ln -s ~/DrupalAppConsole/bin/console /usr/local/sbin/console.dev
```

Now when you want to test the changes in your dev version just execute the command **console.dev**

### Create a custom Phar

If you want to test your changes in a custom Phar before to create a Pull Request following the next steps

#### Install Box

[Box](http://box-project.github.io/box2/) is an application that simplifies the Phar building process. Install in your system executing the following command.

```
$ curl -LSs https://box-project.github.io/box2/installer.php | php
```

The command will create a file **box.phar** used to create Phar files.

#### Create a custom Phar

To create a Phar file base in our dev version execute the following command

```
php box.phar build
```

As result you will the a new file **console.phar**
