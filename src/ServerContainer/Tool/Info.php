<?php

namespace ServerContainer\Tool;



class Info
{
    private $variableRequired;

    function getRequiredArgs() {
        return array('argCount' => 1 );
    }

    function setArg($position, $value) {
        $this->variableRequired = $value;
    }

    function main($variableRequired) {
        $this->variableRequired = $variableRequired;

        $allowedVariables = array(
            'MYSQL_USERNAME',
            'MYSQL_PASSWORD',
            'MYSQL_ROOT_PASSWORD',
            'GITHUB_ACCESS_TOKEN',
        );

        if(in_array($this->variableRequired, $allowedVariables) == true){
            exit(constant($this->variableRequired));
        }

        throw new \Exception("Unknown variable [".$this->variableRequired."]");
    }
}

