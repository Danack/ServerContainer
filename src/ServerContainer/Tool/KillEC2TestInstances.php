<?php

namespace ServerContainer\Tool;

use Aws\Ec2\Ec2Client;
use Aws\Ec2\Exception\Ec2Exception;
use ServerContainer\ServerContainerException;


class KillEC2TestInstances {

    /**
     * @var Ec2Client
     */
    private $ec2;
    
    function __construct($awsKey, $awsSecret) {
        $this->ec2 = createClient('ap-southeast-2', $awsKey, $awsSecret);
    }

    /**
     * 
     */
    function killTestInstances() {
        $testInstances = $this->findTestInstances();

        if(count($testInstances) == 0){
            echo "No test instances to kill.";
        }
        else{
            echo "Test instances to kill are: \r\n";
            foreach($testInstances as $testInstance){
                echo $testInstance."\r\n";
            }

            try {

                
                $response = $this->ec2->terminateInstances($testInstances);
                var_dump($response->toArray());
                echo "Test server should be dead.";
            }
            catch(Ec2Exception $ece) {
                throw new ServerContainerException(
                    "Failed to terminate instance: ".$ece->getMessage(),
                    0,
                    $ece
                );
            }
        }

        echo "\r\n";
    }

    /**
     * @return array
     */
    function    findTestInstances() {
        $testInstances = array();

        try {
            $response = $this->ec2->describeInstances(array(
                'Filters' => array(
                    //array('Name' => 'instance-type', 'Values' => array('m1.small')),
                    array('Name' => 'tag-value', 'Key' => 'Name', 'Values' => array('Testing'))
                )
            ));
        }
        catch(Ec2Exception $ece) {
            throw new ServerContainerException(
                "Failed to describeInstances: ".$ece->getMessage(),
                0,
                $ece
            );
        }

        $data = $response->toArray();

        foreach ($data['Reservations'] as $reservation) {
            foreach ($reservation['Instances'] as $instance) {
                $testInstances[] = $instance['InstanceId'];
            }
        }

        return $testInstances;
    }
}







