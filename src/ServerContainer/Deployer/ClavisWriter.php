<?php


namespace ServerContainer\Deployer;

use Configurator\Configurator;
use ServerContainer\KeyManager\KeyManager;
use ServerContainer\ServerContainerException;

class ClavisWriter
{
    private $env;
    private $outputFilename;
    private $projectName;

    public function __construct(
        KeyManager $keyManager,
        $projectName,
        $environment,
        $clavisFilename,
        $projectKeysFile
    ) {
        $this->env = explode(",", $environment);
        $this->projectName = $projectName;
        $this->outputFilename = $clavisFilename;
        $this->keyManager = $keyManager;
        
        $keysNeededJSON = file_get_contents($projectKeysFile);

        $this->keysNeeded = json_decode($keysNeededJSON);

        if ($this->keysNeeded === null) {
            $msg = sprintf( "JSON error %d : %s",
                json_last_error(),
                json_last_error_msg()
            );
            throw new ServerContainerException("Error decoding JSON ".$msg) ;
        }
    }

    public function writeClavisFile()
    {
        $keys = $this->keyManager->getKeys($this->projectName, $this->keysNeeded);
        $contents = "<?php\n\n";
        $contents .= "function getAppKeys() {\n";
        $contents .= "    static \$keys = [\n";
        
        foreach ($keys as $name => $value) {
            $contents .= "        '$name' => '$value',\n";
        }

        $contents .= "    ];\n\n";
        $contents .= "    return \$keys;\n";
        $contents .= "}\n";
        $contents .= "\n";

        file_put_contents($this->outputFilename, $contents);
    }
}
