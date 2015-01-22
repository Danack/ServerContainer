<?php

namespace ServerContainer\Tool;

use Aws\Ec2\Ec2Client;
use Aws\Ec2\Exception\Ec2Exception;
use ServerContainer\ServerContainerException;
use Aws\Ec2\Enum\InstanceStateName;

define('HOSTNAME_QUERY_MAX_ATTEMPTS', 60);
define('HOSTNAME_QUERY_DELAY', 10);


function generatePassword($passwordLength = 8) {

    $characters = array(
        'a', 'b', 'c', 'd', 'e',
        'e', 'f', 'g', 'h', 'i',
        'j', 'k', /*'l',*/ 'm', 'n',
        'o', 'p', 'q', 'r', 's',
        't', 'u', 'v', 'w', 'x',
        'y', 'z',
        '0', '1', '2', '3', '4',
        '5', '6', '7', '8', '9',
    );

    $password = '';

    for($x=0 ; $x<$passwordLength ; $x++){
        $password .= $characters[rand(0, count($characters) -1)];
    }

    return $password;
}


class EC2Manager {

    /**
     * @var Ec2Client
     */
    private $ec2;
    
    function __construct() {
        $this->ec2 = createClient('ap-southeast-2');
    }

    function logTestInstances() {
        $testInstances = $this->findTestInstances();
        if(count($testInstances) == 0){
            echo "No test instances to get logs for.";
        }
        else{
            echo "Test instances to get log for are: \r\n";
            foreach($testInstances as $testInstance){
                echo $testInstance."\r\n";
                $params = ['InstanceId' => $testInstance];
                $response = $this->ec2->getConsoleOutput($params);
                $data = $response->toArray();
                
                if (isset($data['Output']) == true) {
                    echo base64_decode($data['Output']);
                }
                else {
                    echo "No output yet?\n";
                    var_dump($data);
                }
            }
        }
        echo "\r\n";
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
                $params = ['InstanceIds' => $testInstances];
                
                $response = $this->ec2->terminateInstances($params);
                //var_dump($response->toArray());
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
     * @throws ServerContainerException
     * @throws \Exception
     */
    function launchEC2TestInstance() {
        $username = "ec2-user";
        $ipAddress = '54.252.86.140';

        $tags = array (
            array("Key" => 'Name', "Value" => 'Testing'),
            array("Key" => 'Shamoan', "Value" => 'mothafarjer'),
        );

        $fileContents = $this->getBootstrapScript();
        $userData = base64_encode($fileContents);

        if (count($userData) > 16383 ) {
            throw new ServerContainerException('Startup package exceeds 16KB. Please adjust and try again');
        }

        $response = $this->ec2->runInstances([
            'ImageId'        => AMAZON_MACHINE_IMAGE_NAME,
            'MinCount'       => 1,
            'MaxCount'       => 1,
            'InstanceType'   => AMAZON_EC2_INSTANCE_TYPE,
            'KeyName'        => AMAZON_EC2_SSH_KEY_PAIR_NAME,
            'SecurityGroups' => array(AMAZON_EC2_SECURITY_GROUP),
            "UserData"       => $userData
        ]);

        $data = $response->toArray();

        $instanceID = null;

  
        
        foreach ($data['Instances'] as $instance) {
            $instanceID = $instance['InstanceId'];
            echo "instanceID is $instanceID\n";
        }

        $params = [
            'Resources' => [$instanceID],
            'Tags' => $tags
        ];
        
        $this->ec2->createTags($params);
        
        $this->waitRunning($instanceID);
 
        try{
            echo "Server is up, waiting 5 seconds assign IP address.\n";
            sleep(5);
            $this->associateIPAddress($instanceID, $ipAddress);
        }
        catch(Ec2Exception $e){
            throw new ServerContainerException("Failed to allocated IP address, ", 0, $e->getMessage());
        }

        $hostname = $this->getInstanceProperty($instanceID, 'PublicDnsName');
        $sshCommand = "ssh -i ".AMAZON_EC2_SSH_KEY_PAIR_NAME.".pem $username@".$hostname."\r\n";
        $sshCommand .= "or ssh -i ".AMAZON_EC2_SSH_KEY_PAIR_NAME.".pem $username@test.basereality.com "."\r\n";

        echo  "Connect to the instance using the command:".$sshCommand."\r\n";
    }


    /**
     * @param $instanceID
     * @param $propertyName
     * @return bool
     */
    function getInstanceProperty($instanceID, $propertyName){
        echo "Attempting to get instance description for instance $instanceID \n";

        // Get the hostname from a call to the DescribeImages operation.
        $response = $this->ec2->describeInstances([
            'InstanceIds' => array($instanceID),
        ]);

        $data = $response->toArray();
        $propertyValue = null;

        foreach ($data['Reservations'] as $reservation) {
            foreach ($reservation['Instances'] as $instance) {                
                switch ($propertyName) {
                    case('state'):
                    case('State'): {
                        $propertyValue =  $instance['State']['Name'];
                        break;
                    }
                    
                    default: {
                        if (array_key_exists($propertyName, $instance) == false) {
                            echo "Unknown property $propertyName \n";
                            var_dump($instance);
                            return null;
                        }
                        $propertyValue = $instance[$propertyName];
                    }
                }
            }
        }
        
        return $propertyValue;
    }

    /**
     * @return array
     */
    function findTestInstances() {
        $testInstances = array();

        try {
            $response = $this->ec2->describeInstances(array(
                'Filters' => array(
                    //array('Name' => 'instance-type', 'Values' => array('m1.small')),
                    array('Name' => 'instance-state-name',  'Values' => array('running')),
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

    /**
     * @param $instanceID
     * @param $ipAddress
     * @throws \Exception
     */
    function associateIPAddress($instanceID, $ipAddress) {
        $params = [
            'InstanceId' => $instanceID,
            'PublicIp' => $ipAddress
        ];
        
        try {
            $response = $this->ec2->associateAddress($params);
        }
        catch(Ec2Exception $ec2e) {
            throw new ServerContainerException(
                "Failed to assign IP address: ".$ec2e->getMessage(),
                0,
                $ec2e
            );
        }

        echo "Address $ipAddress should now be associated with instance $instanceID"."\r\n";
    }

    /**
     * @return mixed|string
     */
    function getBootstrapScript() {
        $bootStrapFilename = __DIR__."/../../../scripts/bootstrap/bootStrap.sh";
        $bootstrapFileContents = file_get_contents($bootStrapFilename);

        if ($bootstrapFileContents === false) {
            throw new ServerContainerException("Failed to open $bootStrapFilename to build complete bootstrap.");
        }

        global $clavisList;
        
        if (isset($clavisList) == false) {
            throw new ServerContainerException("clavisList not set.");
        }
        $searchReplaceArray = array();
        foreach ($clavisList as $key) {
            if (defined($key) == false) {
                throw new ServerContainerException("Key $key is not defined.");
            }
            $searchReplaceArray["%$key%"] = constant($key); 
        }

        $searchArray = array_keys($searchReplaceArray);
        $replaceArray = array_values($searchReplaceArray);
        $bootstrapFileContents = str_replace($searchArray, $replaceArray, $bootstrapFileContents);

        return $bootstrapFileContents;
    }


    /**
     * @return bool
     */
    function findUnallocatedIPAddress() {
        $response = $this->ec2->describeAddresses();
        $addressItems = $response->toArray();

        foreach($addressItems['addressesSet']['item'] as $address){
            $domain = 'unknown';

            if(array_key_exists('domain', $address) == TRUE){
                $domain = $address['domain'];
            }
            else{
                echo "Response looks weird. Here is addressItems\n";
                var_dump($addressItems);
            }

            $instanceID = $address['instanceId'];
            $publicIP = $address['publicIp'];

            if(is_array($instanceID) == TRUE && count($instanceID) == 0){
                $instanceID = FALSE;
            }

            echo "domain $domain instanceID $instanceID publicIp $publicIP"."\r\n";
            //It's not being used - lets grab it.
            if($instanceID == FALSE){
                return $publicIP;
            }
        }

        return FALSE;
    }


    /**
     * @return bool
     */
    function getIPAddressToUse() {
        $ipAddress = $this->findUnallocatedIPAddress();
        if($ipAddress == FALSE){
            throw new ServerContainerException("No unallocated ipAddress - cannot continue.");
        }

        echo "ipAddress to use is [".$ipAddress."]\r\n";
        return $ipAddress;
    }

    private function waitRunning($instanceID) {
        $running = FALSE;
        while ($running == FALSE) {
            $state = $this->getInstanceProperty($instanceID, 'state');
            echo "state is ".$state."\r\n";
            if ($state == InstanceStateName::RUNNING) {
                return;
            }
            sleep(2);
        }
    }
}
