<?php

namespace ServerContainer\Tool;



class Info {

    private $variableRequired;
    //private $serverVariableTable;
    
//    
//    function __construct(ServerVariableTable $serverVariableTable) {
//        $this->serverVariableTable = $serverVariableTable;
//    }
    
    
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
//
//        $allowedDBVariables = array(
//            SCRIPTS_VERSION_NUMBER
//        );

//        if(in_array($this->variableRequired, $allowedDBVariables) == true){
//            $query = new YAMLQuery();
//            $query->table($this->serverVariableTable)->whereColumn('name', $this->variableRequired);;
//            $result = $query->fetch();
//
//            if(count($result) > 0){
//                exit($result[0]['ServerVariable.value']);
//            }
//        }

        throw new \Exception("Unknown variable [".$this->variableRequired."]");
    }
}

