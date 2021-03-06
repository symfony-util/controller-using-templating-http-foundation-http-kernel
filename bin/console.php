#!/usr/bin/env php
<?php

/*
 * This file is part of the Symfony-Util package.
 *
 * (c) Jean-Bernard Addor
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Debug\Debug;

// if you don't want to setup permissions the proper way, just uncomment the following PHP line
// read http://symfony.com/doc/current/setup.html#checking-symfony-application-configuration-and-setup
// for more information
//umask(0000);

set_time_limit(0);

chdir(dirname($argv[0]));

require '../vendor/autoload.php';
require '../'.getenv('KERNEL_DIR').'/AppKernel.php';

$input = new ArgvInput();
$env = $input->getParameterOption(['--env', '-e'], getenv('SYMFONY_ENV') ?: 'dev');
$debug = '0' !== getenv('SYMFONY_DEBUG') && !$input->hasParameterOption(['--no-debug', '']) && 'prod' !== $env;

if ($debug) {
    Debug::enable();
}

$kernel = new AppKernel($env, $debug);
$application = new Application($kernel);
// $application->addCommands( ... );
$application->run($input);
