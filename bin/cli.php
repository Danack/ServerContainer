#!/usr/bin/env php
<?php

use Danack\Console\Output\BufferedOutput;
use Danack\Console\Formatter\OutputFormatterStyle;
use Danack\Console\Helper\QuestionHelper;
use Danack\Console\Application;
use Danack\Console\Command\Command;
use ServerContainer\ServerContainerException;
use ArtaxServiceBuilder\Oauth2Token;
use Aws\Ec2\Ec2Client;
use Danack\Console\Input\InputArgument;

require_once(__DIR__.'/../vendor/autoload.php');
require_once __DIR__.'/../../clavis.php';


function echoStackTrace($traceParts) {
    foreach ($traceParts as $tracePart) {
        if (isset($tracePart['file']) && isset($tracePart['line'])) {
            echo $tracePart['file']." ".$tracePart['line']."\n";
        }
        else if (isset($tracePart["function"])) {
            echo $tracePart["function"]."\n";
        }
        else {
            //var_dump($tracePart);
            echo "...\n";
        }
    }
}

function createClient($region) {
    $config = array();
    $config['key'] = AWS_SERVICES_KEY;
    $config['secret'] = AWS_SERVICES_SECRET;
    $config['region'] = $region;

    return Ec2Client::factory($config);
}


function getOauthToken() {
    static $oauthToken = null;
    if ($oauthToken == null) {
        $oauthToken = new Oauth2Token(GITHUB_ACCESS_TOKEN);
    }

    return $oauthToken;
}

/**
 * @return \Auryn\Provider
 */
function setupCLIProvider() {
    $provider = new Auryn\Provider();

    $aliases = [
        'ArtaxServiceBuilder\ResponseCache' =>
        //'ArtaxServiceBuilder\ResponseCache\NullResponseCache'
        'ArtaxServiceBuilder\ResponseCache\FileResponseCache',
    ];

    $provider->delegate('ArtaxServiceBuilder\Oauth2Token', 'getOauthToken');
    
    $params = [
        'cacheDirectory' => realpath(__DIR__."/../var/cache"),
        'tempDirectory' => realpath(__DIR__."/../var/tmp"),
    ];

    foreach ($params as $key => $value) {
        $provider->defineParam($key, $value);
    }

    $provider->delegate('Amp\Reactor', 'Amp\getReactor');
    $provider->share('Amp\Reactor');

    foreach ($aliases as $key => $value) {
        $provider->alias($key, $value);
    }

    $provider->defineParam('userAgent', 'Danack/ServerContainer');
    
    return $provider;
}


$commands = [
    [
        'killEC2Instances',
        ['ServerContainer\Tool\EC2Manager', 'killTestInstances'],
        'Kill any existent EC2 instances'
    ],
    [
        'launchEC2Instance',
        ['ServerContainer\Tool\EC2Manager', 'launchEC2TestInstance'],
        'Launch an EC2 instance of the latest code'
    ],

    [
        'logTestInstances',
        ['ServerContainer\Tool\EC2Manager', 'logTestInstances'],
        'Get the log from the test instance.'
    ],

    [
        'attachIPAddress',
        ['ServerContainer\Tool\EC2Manager', 'attachIPAddressToTest'],
        're-attach the test ip address to the test instance.'
    ],
    
    [
        'deploy',
        ['ServerContainer\Deployer\Deployer', 'run'],
        'Deploy the latest stuff.'
    ],

    [
        'info',
        ['ServerContainer\Tool\Info', 'main'],
        'Get an environment variable',
        'args' => [
            [
                'variableRequired',
                InputArgument::REQUIRED,
                "The file that the conf should be written as."
            ],
        ]
    ],
    
];



//Figure out what Command was requested.
try {
    $console = createConsole($commands);
    $parsedCommand = $console->parseCommandLine();
}
catch(\Exception $e) {
    //@TODO change to just catch parseException when that's implemented 
    $output = new BufferedOutput();
    $console->renderException($e, $output);
    echo $output->fetch();
    exit(-1);
}


//Run the command requested, or the help callable if no command was input
try {
    $output = $parsedCommand->getOutput();
    $formatter = $output->getFormatter();
    $formatter->setStyle('question', new OutputFormatterStyle('blue'));
    $formatter->setStyle('info', new OutputFormatterStyle('blue'));
    $injector = setupCLIProvider();
    $questionHelper = new QuestionHelper();
    $questionHelper->setHelperSet($console->getHelperSet());
    $input = $parsedCommand->getInput();

    $params['inputDir'] = __DIR__."/../";
    $params['outputDir'] = __DIR__."/../";
    $params = array_merge($params, $parsedCommand->getParams());
    
    $injector->execute(
        $parsedCommand->getCallable(),
        formatKeyNames($params)
    );
}
catch(ServerContainerException $sce) {
    echo "Error running task: \n";
    echo $sce->getMessage();
    exit(-1);
}
catch(\Exception $e) {
    echo "Unexpected exception of type ".get_class($e)." running Deployer`: ".$e->getMessage().PHP_EOL;    
    echoStackTrace($e->getTrace());
    
    exit(-2);
}


function formatKeyNames($params) {
    $newParams = [];
    foreach ($params as $key => $value) {
        $newParams[':'.$key] = $value;
    }

    return $newParams;
}


/**
 * Creates a console application with all of the commands attached.
 * @return Application
 */
function createConsole($commands) {
    $console = new Application("Intahwebz", "1.0.0");

    foreach ($commands as $command) {
        $name = $command[0];
        $callable = $command[1];
        $newCommand = new Command($name, $callable);
        $newCommand->setDescription($command[2]);
        
        if (isset($command['args'])) {
            foreach($command['args'] as $arg) {
                $newCommand->addArgument($arg[0], $arg[1], $arg[2]);
            }
        }

        $console->add($newCommand);
    }

    return $console;
}