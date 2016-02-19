<?php

namespace ServerContainer;

use ArtaxServiceBuilder\Oauth2Token;
use Aws\Ec2\Ec2Client;
//use Danack\Console\Application as ConsoleApplication;
use ServerContainer\ConsoleApplication;
use Danack\Console\Output\BufferedOutput;
use Danack\Console\Formatter\OutputFormatterStyle;
use Danack\Console\Helper\QuestionHelper;
use Danack\Console\Command\Command;
use Danack\Console\Input\InputArgument;
use Tier\Executable;
use Tier\InjectionParams;

class App
{
    public static function createClient($region, $key, $secret)
    {
        $s3Config = array();
        
        $s3Config['credentials'] = array( 
             "key" => $key, 
             "secret" => $secret
        );
        $s3Config['region'] = $region;
    
        return Ec2Client::factory($s3Config);
    }

    public static function getOauthToken(Config $config)
    {
        static $oauthToken = null;
        if ($oauthToken == null) {
            $accessToken = $config->getKey(Config::GITHUB_ACCESS_TOKEN);
            $oauthToken = new Oauth2Token($accessToken);
        }
    
        return $oauthToken;
    }
    
    public static function createEC2Manager(Config $config)
    {
        return new \ServerContainer\Tool\EC2Manager(
            $config->getKey(Config::SERVER_CONTAINER_AWS_SERVICES_KEY),
            $config->getKey(Config::SERVER_CONTAINER_AWS_SERVICES_SECRET)
        );
    }

    public static function createKillEC2TestInstances(Config $config)
    {
        return new \ServerContainer\Tool\KillEC2TestInstances(
            $config->getKey(Config::SERVER_CONTAINER_AWS_SERVICES_KEY),
            $config->getKey(Config::SERVER_CONTAINER_AWS_SERVICES_SECRET)
        );
    }

    public static function figureOutWhatCommandWasRun(ConsoleApplication $console)
    {    
        //Figure out what Command was requested.
        try {
            $parsedCommand = $console->parseCommandLine();
        }
        catch (\Exception $e) {
            //@TODO change to just catch parseException when that's implemented 
            $output = new BufferedOutput();
            $console->renderException($e, $output);
            echo $output->fetch();
            exit(-1);
        }
    
        $output = $parsedCommand->getOutput();
        $formatter = $output->getFormatter();
        $formatter->setStyle('question', new OutputFormatterStyle('blue'));
        $formatter->setStyle('info', new OutputFormatterStyle('blue'));
        $questionHelper = new QuestionHelper();
        $questionHelper->setHelperSet($console->getHelperSet());
        $injectionParams = InjectionParams::fromParams($parsedCommand->getParams());
        
        $executable = new Executable($parsedCommand->getCallable(), $injectionParams);
        $executable->setAllowedToReturnNull(true);
    
        return $executable;
    }

    public static function handleUserErrorMessageException(UserErrorMessageException $me)
    {
        echo $me->getMessage();
        echo PHP_EOL;
        exit(-1);
    }
    
    public static function handleServerContainerException(ServerContainerException $sce)
    {
        echo "Error running task: \n";
        echo $sce->getMessage();
        exit(-1);
    }
}



