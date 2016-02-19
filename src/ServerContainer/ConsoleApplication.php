<?php


namespace ServerContainer;

use Danack\Console\Application;
use Danack\Console\Command\Command;
use Danack\Console\Input\InputArgument;

class ConsoleApplication extends Application
{
    /**
     * Creates a console application with all of the commands attached.
     * @return ConsoleApplication
     */
    public function __construct()
    {
        parent::__construct("ServerDeployer", "1.0.0");

        $commands = [];
        $commands[] = [
                'killEC2Instances',
                ['ServerContainer\Tool\EC2Manager', 'killTestInstances'],
                'Kill any existent EC2 instances'
        ];
        $commands[] = [
                'launchEC2Instance',
                ['ServerContainer\Tool\EC2Manager', 'launchEC2TestInstance'],
                'Launch an EC2 instance of the latest code'
        ];
        
        $commands[] = [
                'logTestInstances',
                ['ServerContainer\Tool\EC2Manager', 'logTestInstances'],
                'Get the log from the test instance.'
        ];
        $commands[] = [
            'attachIPAddress',
            ['ServerContainer\Tool\EC2Manager', 'attachIPAddressToTest'],
            're-attach the test ip address to the test instance.'
        ];
        $commands[] = [
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
        ];

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
    
            $this->add($newCommand);
        }
    
        $this->add(self::getDeployCommand());
        $this->add(self::getEnvWriteCommand());
        $this->add(self::getWriteClavisCommand());
    }

    public static function getWriteClavisCommand()
    {
        $writeClavisCommand = new Command('writeClavisFile', ['ServerContainer\Deployer\ClavisWriter', 'writeClavisFile']);
        $writeClavisCommand->setDescription("Write a key file for a project.");
        $writeClavisCommand->addArgument('projectName', InputArgument::REQUIRED, 'The project name');
        $writeClavisCommand->addArgument('environment', InputArgument::REQUIRED, 'The environment setting');
        $writeClavisCommand->addArgument('clavisFilename',
            InputArgument::REQUIRED,
            'The clavis filename to be written to.'
        );
    
        $writeClavisCommand->addArgument('projectKeysFile',
            InputArgument::REQUIRED,
            'The name of a json file that contains a list of keys needed by the app.'
        );
    
        return $writeClavisCommand;
    }
    
    public static function getEnvWriteCommand()
    {
        $envWriteCommand = new Command('genEnvSettings', ['ServerContainer\Deployer\EnvConfWriter', 'writeEnvFile']);
        $envWriteCommand->setDescription("Write an env setting bash script.");
        $envWriteCommand->addArgument('projectName',
            InputArgument::REQUIRED,
            'The project name. This will be prepended to the env vars.'
        );
    
        $envWriteCommand->addArgument('env',
            InputArgument::REQUIRED,
            'Which environment the settings should be generated for.'
        );
        $envWriteCommand->addArgument('keysFilename', InputArgument::REQUIRED, 'The input keys');
        $envWriteCommand->addArgument('outputFilename',
            InputArgument::REQUIRED,
            'The file name that the env settings should be written to, e.g. /etc/profile.d/projectName.sh'
        );
        
        return $envWriteCommand;
    }
    
    public static function getDeployCommand()
    {
        $deployCommand = new Command('deploy', ['ServerContainer\Deployer\Deployer', 'run']);
        $deployCommand->setDescription('Deploy an application.');
        $deployCommand->addArgument(
            'application',
            // This is optional so as to allow use to give the error message, instead
            // of the console app providing a rubbish one.
            InputArgument::OPTIONAL,
            "Which application should be deployed."
        );
        
        return $deployCommand;
    }
}
