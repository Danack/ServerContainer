<?php

namespace ServerContainer\KeyManager;

use ServerContainer\ServerContainerException;

require_once __DIR__."/../../../../clavis.php";

class KeyManager
{
    private $keys;
    
    public function __construct()
    {
        $this->keys = getKeysServerContainer();
    }
    
    public function getKeys($projectName, $keysNeeded)
    {
        $appKeys = [];

        foreach ($keysNeeded as $keyNeeded) {
            if (array_key_exists($keyNeeded, $this->keys) == false) {
                throw new ServerContainerException("App wants key '$keyNeeded' but not available.");
            }
            $appKeys[$keyNeeded] = $this->keys[$keyNeeded];
        }

        return $appKeys;
    }
}
