<?php

namespace ServerContainer\Tool;

use Aws\Ec2\Ec2Client;

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


class LaunchEC2TestInstance {

    /**
     * @var Ec2Client
     */
    private $ec2;
    
    function __construct() {
        $this->ec2 = createClient('ap-southeast-2');
    }

    function main() {
        $username = "ec2-user";
        $ipAddress = '54.252.86.140';

        $tags = array (
            array("Key" => 'Name', "Value" => 'Testing'),
            array("Key" => 'Shamoan', "Value" => 'mothafarjer'),
        );

        $instanceID = $this->createEC2Instance(
            AMAZON_MACHINE_IMAGE_NAME,
            AMAZON_EC2_INSTANCE_TYPE,
            AMAZON_EC2_SSH_KEY_PAIR_NAME,
            AMAZON_EC2_SECURITY_GROUP
        );
    
        $running = FALSE;

        while($running == FALSE){
            $state = $this->getInstanceProperty($instanceID, 'state');

            echo "state is ".$state."\r\n";

            if($state == 'running'){
                $running = TRUE;
            }
            sleep(2);
        }

        echo "fin";
        exit(0);
        
        try{
            echo "Server is up, waiting 5 seconds assign IP address.\n";
            sleep(5);
            $this->associateIPAddress($instanceID, $ipAddress);
        }
        catch(\Exception $e){
            echo "Tried, failed ".$e;
        }

        echo "Tried succeeded";

        $hostname = $this->getInstanceProperty($instanceID, 'hostname');

        $sshCommand = "ssh -i ".AMAZON_EC2_SSH_KEY_PAIR_NAME.".pem $username@".$hostname."\r\n";
        $sshCommand .= "or ssh -i ".AMAZON_EC2_SSH_KEY_PAIR_NAME.".pem $username@test.basereality.com "."\r\n";

        $this->ec2->createTags($instanceID, $tags);

        echo  "Connect to the instance using the command:".$sshCommand."\r\n";
    }


    /**
     * @param $instanceID
     * @param $propertyName
     * @return bool
     */
    function getInstanceProperty($instanceID, $propertyName){
        //max time 3 minutes
        $hostname = FALSE;
        $hostnameQueryCount = 0;

        do{
            echo "Attempting to get instance description for instance $instanceID ";

            // Get the hostname from a call to the DescribeImages operation.
            $response = $ec2->describe_instances(
                array(
                    'Filter' => array(
                        array('Name' => 'instance-id',
                              'Value' => $instanceID,
                        ),
                    )
                )
            );

            if ($response->isOK() == FALSE) {
                return FALSE;
            }

            /** @noinspection PhpUndefinedFieldInspection */
            /** @noinspection PhpUndefinedFieldInspection */
            if($propertyName == 'hostname'&& isset($response->body->reservationSet->item->instancesSet->item->dnsName) == TRUE){

                /** @noinspection PhpUndefinedFieldInspection */
                $hostname = $response->body->reservationSet->item->instancesSet->item->dnsName;
                echo " hostname is $hostname"."\r\n";
                return $hostname;
            }

            else /** @noinspection PhpUndefinedFieldInspection */
                /** @noinspection PhpUndefinedFieldInspection */
            if($propertyName == 'state' && isset($response->body->reservationSet->item->instancesSet->item->instanceState->name) == TRUE){
                /** @noinspection PhpUndefinedFieldInspection */
                return $response->body->reservationSet->item->instancesSet->item->instanceState->name;
            }
            else{
                echo "...failed to get description, waiting a bit."."\r\n";
                sleep (HOSTNAME_QUERY_DELAY);		// Give instance some time to start up
                $hostnameQueryCount++;
            }

        }
        while($hostname == FALSE && $hostnameQueryCount <= HOSTNAME_QUERY_MAX_ATTEMPTS);

        /** @noinspection PhpUndefinedFieldInspection */
        $hostname = $response->body->reservationSet->item->instancesSet->item->dnsName;

        return $hostname;
    }

    /**
     * @param $instanceID
     * @param $ipAddress
     * @throws \Exception
     */
    function    associateIPAddress($instanceID, $ipAddress) {
        $count = 0;
        $finished = FALSE;

        while ($finished == FALSE) {

            $params = [
                'InstanceId' => $instanceID,
                'PublicIp' => $ipAddress
            ];
            
            $response = $this->ec2->associateAddress($params);
            if ($response->isOK() == TRUE) {
                $finished = TRUE;
                echo "Address $ipAddress should now be associated with instance $instanceID"."\r\n";
            }
            else {
                echo "Assigning IP failed, retry in a few moments" . "\r\n";
                sleep(2);
                $count++;

                if ($count > 10) {
                    var_dump($response);

                    throw new \Exception("Failed to associate instance with ipAddress: " . $ipAddress . " Response was [ " . $response->body . "].");
                }
            }
        }
    }

    //TODO - this should be done on the deployed instance. Passing it in via the bootscript
    //is a security hole, as is the whole of the Amazon ec2 data centre.
    function getRootConfigGeneration(){

        $SQL_SITE_USERNAME = 'baseSQL';
        $SQL_SITE_PASSWORD = generatePassword();
        $SQL_ROOT_PASSWORD = generatePassword();
        //This actually increments the version number on the machine that is deploying the
        //instance, not on the instance itself. Will probably lead to confusion in the future.
        //$scriptVersion = $this->serverVariableMapper->incrementAndReturnVersionNumber();
        
        $flickrKey = FLICKR_KEY;
        $flickrSecret = FLICKR_SECRET;

        $AWS_SERVICES_KEY = AWS_SERVICES_KEY;
        $AWS_SERVICES_SECRET = AWS_SERVICES_SECRET;
        $MYSQL_SERVER = "127.0.0.1";
        $MYSQL_SOCKET_CONNECTION = null;

        $rootConfigGen = <<< END
    # generated by getRootConfigGeneration()

    echo "<?php" > \$configFile
    
    echo "//this R config script" >> \$configFile;

END;

        return $rootConfigGen;
    }

    /**
     * @return mixed|string
     */
    function getBootstrapScript() {
        $rootConfigFile = $this->getRootConfigGeneration();

        $searchString = '##CreateIntahwebzConf';
        $bootStrapFilename = __DIR__."/scripts/bootStrap.sh";
        $bootstrapFileContents = file_get_contents($bootStrapFilename);

        if ($bootstrapFileContents === false) {
            echo "Failed to open $bootStrapFilename";
            exit(-1);
        }

        $searchReplaceArray = array(
            '%GITHUB_REPO_NAME%' => GITHUB_REPO_NAME,
            '%GITHUB_ACCESS_TOKEN%' => GITHUB_ACCESS_TOKEN,
        );

        $bootstrapFileContents = str_replace($searchString, $rootConfigFile, $bootstrapFileContents);

        $searchArray = array_keys($searchReplaceArray);
        $replaceArray = array_values($searchReplaceArray);

        $bootstrapFileContents = str_replace($searchArray, $replaceArray, $bootstrapFileContents);

        return $bootstrapFileContents;
    }

    /**
     * @param $ami
     * @param $instanceType
     * @param $keyName
     * @param $securityGroup
     * @return mixed
     * @throws ExternalAPIFailedException
     */
    function createEC2Instance($ami, $instanceType, $keyName, $securityGroup) {
        $fileContents = $this->getBootstrapScript();
        $userData = base64_encode($fileContents);

        if (count($userData) > 16383 ) {
            throw new \Exception('Startup package exceeds 16KB. Please adjust and try again');
        }

        echo "Creating instance...";

        // Boot an instance of the image
        $response = $this->ec2->runInstances(
                        $ami,
                        1,
                        1,
                        array(
                            'KeyName' => $keyName,
                            'InstanceType' => $instanceType,
                            'SecurityGroupId' => $securityGroup,
                            "UserData" => $userData
                        )
        );

        var_dump($response->toArray());

        /** @noinspection PhpUndefinedFieldInspection */
        //$instanceID = $response->body->instancesSet->item->instanceId;

        //echo "instance created with ID $instanceID"."\r\n";
        $instanceID = '';

        return $instanceID;
    }

    /**
     * @return bool
     */
    function findUnallocatedIPAddress() {

        $response = $this->ec2->describeAddresses();

        $addressItems = $response->toArray();
        
        var_dump($addressItems);
        exit(0);
        

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
            echo "//*****************************"."\r\n";
            echo "No unallocated ipAddress - cannot continue."."\r\n";
            echo "//*****************************"."\r\n";

            exit(0);
            
            //$response = $this->ec2->allocateAddress();
//            
//            if ($response->isOK() == FALSE) {
//                throw new ExternalAPIFailedException("Failed to find unallocated ipAddress to use, and failed to allocated one.");
//            }
//            $ipAddress = $this->findUnallocatedIPAddress();
        }

        echo "ipAddress to use is [".$ipAddress."]\r\n";
        return $ipAddress;
    }

}




/*
 
echo "define('SCRIPTS_VERSION', '${scriptVersion}');" >> \$configFile

echo "define('LIVE_SERVER', TRUE);" >> \$configFile
echo "define('CONTENT_BUCKET', 'content.basereality.com');" >> \$configFile
echo "define('BACKUP_BUCKET', 'backup.basereality.com');" >> \$configFile
echo "define('STATIC_BUCKET', 'static.basereality.com');" >> \$configFile

echo "define('MYSQL_PORT', 3306);" >> \$configFile
echo "define('$MYSQL_SOCKET_CONNECTION', '${MYSQL_SOCKET_CONNECTION}');" >> \$configFile
echo "define('MYSQL_SERVER', '${MYSQL_SERVER}');" >> \$configFile

echo "define('MYSQL_ROOT_USERNAME', 'root');" >> \$configFile
echo "define('MYSQL_ROOT_PASSWORD', '${SQL_ROOT_PASSWORD}');" >> \$configFile

echo "define('MYSQL_USERNAME', '${SQL_SITE_USERNAME}');" >> \$configFile
echo "define('MYSQL_PASSWORD', '${SQL_SITE_PASSWORD}');" >> \$configFile

echo "define('MYSQL_SOCKET_CONNECTION', '/var/lib/mysql/mysql.sock');" >> \$configFile
echo "//need to define where the socket is:" >> \$configFile


echo "define('CDN_CNAMES', 5);" >> \$configFile
echo "define('CDN_ENABLED', TRUE);" >> \$configFile
echo "define('ROOT_DOMAIN', 'basereality.com');" >> \$configFile
echo "define('BLOG_ROOT_DOMAIN', 'blog.basereality.com');" >> \$configFile


echo "define('AWS_SERVICES_KEY', '$AWS_SERVICES_KEY');" >> \$configFile
echo "define('AWS_SERVICES_SECRET', '$AWS_SERVICES_SECRET');" >> \$configFile

echo "define('FLICKR_KEY', '$flickrKey');" >> \$configFile
echo "define('FLICKR_SECRET', '$flickrSecret');" >> \$configFile

echo "" >> \$configFile



 */