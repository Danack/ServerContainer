<?php

namespace ServerContainer\Tool;

use Aws\Ec2\Ec2Client;



class KillEC2TestInstances {

    /**
     * @var Ec2Client
     */
    private $ec2;
    
    function __construct() {
        $this->ec2 = createClient('ap-southeast-2');
    }

    /**
     * 
     */
    function main() {
        $testInstances = $this->findTestInstances();

        if(count($testInstances) == 0){
            echo "No test instances to kill.";
        }
        else{
            echo "Test instances to kill are: \r\n";
            foreach($testInstances as $testInstance){
                echo $testInstance."\r\n";
            }
                        
            $response = $this->ec2->terminateInstances($testInstances);
            var_dump($response->toArray());
            echo "Test server should be dead.";
        }

        echo "\r\n";
    }

    /**
     * @return array
     */
    function    findTestInstances() {
        $testInstances = array();
        $response = $this->ec2->describeInstances(array(
//            'Filters' => array(
//                array('Name' => 'instance-type', 'Values' => array('m1.small')),
//            )
        ));

        $response = $response->toArray();
        
        $reservations = $response['Reservations'];
        foreach ($reservations as $reservation) {
            $instances = $reservation['Instances'];
            foreach ($instances as $instance) {

                $instanceName = '';
                foreach ($instance['Tags'] as $tag) {
                    if ($tag['Key'] == 'Name') {
                        $instanceName = $tag['Value'];
                    }

                    if ($tag['Key'] == 'Name' && $tag['Value'] == 'Testing') {
                        $testInstances[] = $instance['InstanceId'];
                    }
                }

                echo 'Instance Name: ' . $instanceName . PHP_EOL;
                echo '---> State: ' . $instance['State']['Name'] . PHP_EOL;
                echo '---> Instance ID: ' . $instance['InstanceId'] . PHP_EOL;
                echo '---> Image ID: ' . $instance['ImageId'] . PHP_EOL;
                echo '---> Private Dns Name: ' . $instance['PrivateDnsName'] . PHP_EOL;
                echo '---> Instance Type: ' . $instance['InstanceType'] . PHP_EOL;
                echo '---> Security Group: ' . $instance['SecurityGroups'][0]['GroupName'] . PHP_EOL;
            }
        }

        return $testInstances;
    }
}







