#!/usr/bin/env php
<?php

/*
 * This file is part of the Certificationy CLI application.
 *
 * (c) Vincent Composieux <vincent.composieux@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;

$app = new Application();
$app->add(new \Certificationy\Command\TestCommand());
$app->add(new \Certificationy\Command\ListCommand());
$app->setDefaultCommand('test');
$app->run();
