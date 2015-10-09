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
use ServerContainer\MessageException;
use Auryn\Injector;
use ServerContainer\Config;

require_once(__DIR__.'/../vendor/autoload.php');
require_once __DIR__.'/../../clavis.php';


require_once __DIR__.'/../../settings.php';

function exceptionHandler(Exception $ex)
{
    //TODO - need to ob_end_clean as many times as required because
    //otherwise partial content gets sent to the client.

    if (headers_sent() == false) {
        header("HTTP/1.0 500 Internal Server Error", true, 500);
    }
    else {
        //Exception after headers sent
    }

    while ($ex) {
        echo "Exception " . get_class($ex) . ': ' . $ex->getMessage()."<br/>";

        foreach ($ex->getTrace() as $tracePart) {
            if (isset($tracePart['file']) && isset($tracePart['line'])) {
                echo $tracePart['file'] . " " . $tracePart['line'] . "<br/>";
            }
            else if (isset($tracePart["function"])) {
                echo $tracePart["function"] . "<br/>";
            }
            else {
                var_dump($tracePart);
            }
        }
        $ex = $ex->getPrevious();
        if ($ex) {
            echo "Previously ";
        }
    };
}

function errorHandler($errno, $errstr, $errfile, $errline)
{
    if (error_reporting() == 0) {
        return true;
    }
    if ($errno == E_DEPRECATED) {
        //lol don't care.
        return true;
    }

    $errorNames = [
        E_ERROR => "E_ERROR",
        E_WARNING => "E_WARNING",
        E_PARSE => "E_PARSE",
        E_NOTICE => "E_NOTICE",
        E_CORE_ERROR => "E_CORE_ERROR",
        E_CORE_WARNING => "E_CORE_WARNING",
        E_COMPILE_ERROR => "E_COMPILE_ERROR",
        E_COMPILE_WARNING => "E_COMPILE_WARNING",
        E_USER_ERROR => "E_USER_ERROR",
        E_USER_WARNING => "E_USER_WARNING",
        E_USER_NOTICE => "E_USER_NOTICE",
        E_STRICT => "E_STRICT",
        E_RECOVERABLE_ERROR => "E_RECOVERABLE_ERROR",
        E_DEPRECATED => "E_DEPRECATED",
        E_USER_DEPRECATED => "E_USER_DEPRECATED",
    ];
    
    $errorType = "Error type $errno";

    if (array_key_exists($errno, $errorNames)) {
        $errorType = $errorNames[$errno];
    }

    $message =  "$errorType: [$errno] $errstr in file $errfile on line $errline";

    throw new \LogicException($message);
}


function fatalErrorShutdownHandler()
{
    $fatals = [
        E_ERROR,
        E_PARSE,
        E_USER_ERROR,
        E_CORE_ERROR,
        E_CORE_WARNING,
        E_COMPILE_ERROR,
        E_COMPILE_WARNING
    ];
    $lastError = error_get_last();

    if ($lastError && in_array($lastError['type'], $fatals)) {
//        if (headers_sent()) {
//            return;
//        }
        header_remove();
        header("HTTP/1.0 500 Internal Server Error");
        
        extract($lastError);
        $errorMessage = sprintf("Fatal error: %s in %s on line %d", $message, $file, $line);

        error_log($errorMessage);
        $msg = "Oops! Something went terribly wrong :(";

        //$msg = "<pre style=\"color:red;\">{$msg}</pre>";
        $msg = sprintf(
            "<pre style=\"color:red;\">%s</pre>",
            $errorMessage
        );

        echo "<html><body><h1>500 Internal Server Error</h1><hr/>{$msg}</body></html>";
    }
}


register_shutdown_function('fatalErrorShutdownHandler');
set_exception_handler('exceptionHandler');
set_error_handler('errorHandler');



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

function createClient($region, $key, $secret)
{
    $s3Config = array();
    
    $s3Config['credentials'] = array( 
         "key" => $key, 
         "secret" => $secret
    );
    $s3Config['region'] = $region;

    return Ec2Client::factory($s3Config);
}


function getOauthToken(Config $config)
{
    static $oauthToken = null;
    if ($oauthToken == null) {
        $accessToken = $config->getKey(Config::GITHUB_ACCESS_TOKEN);
        $oauthToken = new Oauth2Token($accessToken);
    }

    return $oauthToken;
}

/**
 * @return \Auryn\Injector
 */
function setupCLIProvider() {
    $provider = new Injector();

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
    $provider->delegate('ServerContainer\Tool\EC2Manager', 'createEC2Manager');
    $provider->delegate('ServerContainer\Tool\KillEC2TestInstances', 'createKillEC2TestInstances');
    
    
    $provider->share('Amp\Reactor');

    foreach ($aliases as $key => $value) {
        $provider->alias($key, $value);
    }

    $provider->defineParam('userAgent', 'Danack/ServerContainer');
    
    return $provider;
}


function createEC2Manager(Config $config)
{

    return new \ServerContainer\Tool\EC2Manager(
        $config->getKey(Config::SERVER_CONTAINER_AWS_SERVICES_KEY),
        $config->getKey(Config::SERVER_CONTAINER_AWS_SERVICES_SECRET)
    );
}

function createKillEC2TestInstances(Config $config)
{
    return new \ServerContainer\Tool\KillEC2TestInstances(
        $config->getKey(Config::SERVER_CONTAINER_AWS_SERVICES_KEY),
        $config->getKey(Config::SERVER_CONTAINER_AWS_SERVICES_SECRET)
    );
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
//    [
//        'deploy',
//        ['ServerContainer\Deployer\Deployer', 'run'],
//        'Deploy an application.',
//        'args' => [
//            [
//                'application',
//                InputArgument::REQUIRED,
//                "Which application should be deployed."
//            ],
//        ]
//    ],
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

    foreach ($parsedCommand->getParams() as $key => $value) {
        //echo "key $key, value $value\n";
        $injector->defineParam($key, $value);
    }

    $injector->execute($parsedCommand->getCallable());
}
catch (MessageException $me) {
    echo $me->getMessage();
    echo PHP_EOL;
    exit(-1);
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

    $deployCommand = new Command('deploy', ['ServerContainer\Deployer\Deployer', 'run']);
    $deployCommand->setDescription('Deploy an application.');
    //$deployCommand->addArgument('application', InputArgument::REQUIRED, "Which application should be deployed.");
    $deployCommand->addArgument(
        'application',
        InputArgument::OPTIONAL,
        "Which application should be deployed."
    );
    $console->add($deployCommand);
    
    $envWriteCommand = new Command('genEnvSettings', ['ServerContainer\Deployer\EnvConfWriter', 'writeEnvFile']);  
    $envWriteCommand->setDescription("Write an env setting bash script.");
    $envWriteCommand->addArgument('projectName', InputArgument::REQUIRED, 'The project name. This will be prepended to the env vars.');
    
    $envWriteCommand->addArgument('env', InputArgument::REQUIRED, 'Which environment the settings should be generated for.');
    $envWriteCommand->addArgument('keysFilename', InputArgument::REQUIRED, 'The input keys');
    $envWriteCommand->addArgument('outputFilename', InputArgument::REQUIRED, 'The file name that the env settings should be written to, e.g. /etc/profile.d/projectName.sh');
    $console->add($envWriteCommand);
    
    
    $writeClavisCommand = new Command('writeClavisFile', ['ServerContainer\Deployer\ClavisWriter', 'writeClavisFile']);
    $writeClavisCommand->setDescription("Write a key file for a project.");
    $writeClavisCommand->addArgument('projectName', InputArgument::REQUIRED, 'The project name');
    $writeClavisCommand->addArgument('environment', InputArgument::REQUIRED, 'The environment setting');
    $writeClavisCommand->addArgument('clavisFilename', InputArgument::REQUIRED, 'The clavis filename to be written to.');
    
    $writeClavisCommand->addArgument('projectKeysFile', InputArgument::REQUIRED, 'The name of a json file that contains a list of keys needed by the app.');
    
    
    $console->add($writeClavisCommand);
        
    

    return $console;
}