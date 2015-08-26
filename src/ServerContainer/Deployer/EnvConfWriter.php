<?php


namespace ServerContainer\Deployer;

use Configurator\Configurator;

class EnvConfWriter
{
    private $env;
    private $keysFilename;
    private $outputFilename;
    private $projectName;

    public function __construct($projectName, $env, $keysFilename, $outputFilename)
    {
        $this->env = $env;
        $this->keysFilename = $keysFilename;
        $this->outputFilename = $outputFilename;
        $this->projectName = $projectName;
        
        $knownEnvironments = [
            'live',
            'dev'
        ];
        
        if (in_array($env, $knownEnvironments) == false) {
            throw new \Exception("Environment '$env' is not known, use one of ".implode(', ', $knownEnvironments));
        }
    }

    public function writeEnvFile()
    {
        $configurator = new Configurator();
        $configurator->addPHPConfig($this->env, $this->keysFilename);
        $contents = "";
        $config = $configurator->getConfig();

        foreach ($config as $key => $value) {
            $key = str_replace('.', "_", $key);
            
            $nextEntry = sprintf(
                "export \"%s_%s\"=\"%s\"\n",
                $this->projectName,
                $key,
                $value
            );
            $contents .= $nextEntry;
        }

        file_put_contents($this->outputFilename, $contents);
    }
}
