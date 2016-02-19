#!/usr/bin/env php
<?php

use Auryn\Injector;
use Tier\TierCLIApp;
use Tier\CLIFunction;

ini_set('display_errors', 'on');

require_once(__DIR__.'/../vendor/autoload.php');

CLIFunction::setupErrorHandlers();

// We're on the CLI - let errors be seen.
// ini_set('display_errors', 'off');

require_once __DIR__.'/../../clavis.php';
require_once __DIR__.'/../settings.php';
$injectionParams = require_once __DIR__.'/injectionParams.php';

$injector = new Injector();

// TODO - Replace with proper types.
$injector->defineParam('inputDir', __DIR__."/../");
$injector->defineParam('outputDir', __DIR__."/../");

$exceptionResolver = TierCLIApp::createStandardExceptionResolver();

$exceptionResolver->addExceptionHandler(
    'ServerContainer\UserErrorMessageException',
    ['ServerContainer\App', 'handleUserErrorMessageException']
);

$exceptionResolver->addExceptionHandler(
    'ServerContainer\ServerContainerException',
    ['ServerContainer\App', 'handleServerContainerException']
);

$tierApp = new TierCLIApp(
    $injectionParams,
    $injector,
    $exceptionResolver
);

$tierApp->addInitialExecutable('Tier\Bridge\ConsoleRouter::routeCommand');
$tierApp->execute();
