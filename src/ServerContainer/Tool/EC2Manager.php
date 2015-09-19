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
        //$this->ec2 = createClient('ap-southeast-2');
        $this->ec2 = createClient('eu-west-1');
        
    }

    function attachIPAddressToTest() {
        $testInstances = $this->findTestInstances();
        $ipAddress = '54.252.86.140';

        foreach($testInstances as $testInstance) {
            $this->associateIPAddress($testInstance, $ipAddress);
            return;
        }

        $this->getIPAddressInfo();
    }
    
    function getIPAddressInfo() {

        //$ipAddress = '54.252.86.140';

        $ipAddress = '52.64.1.109';
        
        $params = array(
            'PublicIps' => array($ipAddress),
        );
        $response = $this->ec2->describeAddresses($params);
        
        var_dump($response);
        
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
            //array("Key" => 'Shamoan', "Value" => 'mothafarjer'),
        );

        $fileContents = $this->getBootstrapScript();

        $userData = base64_encode($fileContents);
        if (count($userData) > 16383 ) {
            throw new ServerContainerException('Startup package exceeds 16KB. Please adjust and try again');
        }

        $placement = array(
            'AvailabilityZone' => 'eu-west-1a',
            'Tenancy' => 'default'
        );

        $response = $this->ec2->runInstances([
            'ImageId' => AMAZON_MACHINE_IMAGE_NAME,
            'MinCount' => 1,
            'MaxCount' => 1,
            'InstanceType' => AMAZON_EC2_INSTANCE_TYPE,
            'KeyName' => AMAZON_EC2_SSH_KEY_PAIR_NAME,
            //'SecurityGroups' => array(AMAZON_EC2_SECURITY_GROUP),
            'SecurityGroupIds' => array(AMAZON_EC2_SECURITY_GROUP),
            //"UserData" => $userData,
            'SubnetId' => AMAZON_EC2_VPC,
            //'Placement' => $placement
            'Ebs' => [
                'DeleteOnTermination' => true,
                'VolumeSize' => 10,
            ],

//            'IamInstanceProfile' => array(
//                'Arn' => 'string',
//                'Name' => 'string',
//            ),
            
            //'UserData' => base64_encode()
                
            ]
        );
        



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

        $ipAllocted = false;
       
        for ($x=0; ($x<10) && ($ipAllocted == false); $x++) {
            try {
                echo "Server is up, waiting 5 seconds assign IP address.\n";
                sleep(5);
                $this->associateIPAddress($instanceID, $ipAddress);
                $ipAllocted = true;
            }
            catch(Ec2Exception $e) {
                //should save $e   
            }
        }
        
        if ($ipAllocted == false) {
            throw new ServerContainerException("Failed to allocated IP address, ");
        }

        $hostname = $this->getInstanceProperty($instanceID, 'PublicDnsName');
        $sshCommand = "ssh -i ".AMAZON_EC2_SSH_KEY_PAIR_NAME.".pem $username@".$hostname."\r\n";
        $sshCommand .= "or ssh -i ".AMAZON_EC2_SSH_KEY_PAIR_NAME.".pem $username@test.basereality.com "."\r\n";

        echo  "Connect to the instance using the command:".$sshCommand."\r\n";
        echo "and then run\n";
        
        echo $fileContents;
        
        
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
            //'PublicIp' => $ipAddress,
            //'AllocationId' => "eipalloc-2001e745"
            'AllocationId' => "eipalloc-4c18ea29"
        ];
        
        try {
            $this->ec2->disassociateAddress([
                    'AssociationId' => $params['AllocationId'],
                    //'DryRun' => false,
                    //'PublicIp' => '<string>',
                ]
            );
        }
        catch(Ec2Exception $ec2e) {
            echo "disassociateAddress failed - but we don't care\n";
        }
                
        
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

    function getBootstrapScript()
    {
$cloundInitScript = <<< 'SCRIPT'
#!/bin/bash

read -r -d '' contents <<'EOF'
# http://serverfault.com/questions/148341/linux-schedule-command-to-run-once-after-reboot-runonce-equivalent
# Copy this file to /usr/local/bin/runonce
# and add this entry to crontab
# @reboot     /usr/local/bin/runonce.sh

mkdir -p /etc/local/runonce.d/ran

for file in /etc/local/runonce.d/*
do
    if [ ! -f "$file" ]
    then
        continue
    fi
    "$file"
    basename=$(basename "$file")
    mv "$file" "/etc/local/runonce.d/ran/$basename.$(date +%Y%m%dT%H%M%S)"
    logger -t runonce -p local3.info "$file"
done
EOF

echo "$contents" > /usr/local/bin/runonce.sh
chmod +x /usr/local/bin/runonce.sh

read -r -d '' contents <<'EOF'
#!/bin/sh

mkdir -p /tmp/install
cd /tmp/install

mkdir -p /home/servercontainer/servercontainer

#wget --output-document=/tmp/install/srcBootstrap.sh --no-check-certificate https://raw.githubusercontent.com/Danack/ServerContainer/master/scripts/bootStrap.sh

#sh /tmp/install/srcBootstrap.sh

# yum -y erase python-setuptools




cd /tmp
curl -L "https://github.com/Danack/ServerContainer/archive/master.tar.gz" -o "master.tgz"
#wget -O master.tgz https://github.com/Danack/ServerContainer/archive/master.tar.gz
tar -xvf master.tgz
cd ./ServerContainer-master/scripts
sh ./bootstrap/bootStrap.sh

EOF

mkdir -p /etc/local/runonce.d

echo "$contents" > /etc/local/runonce.d/installServerContainer.sh

chmod +x /etc/local/runonce.d/installServerContainer.sh

(crontab -u root -l; echo "@reboot     /usr/local/bin/runonce.sh" ) | crontab -u root -

mkdir -p /home/servercontainer


SCRIPT;
        //$cloundInitScript .= $this->getClavisWritingScript();
        
        $cloundInitScript .= "
         
            # reboot 
        ";

        return $cloundInitScript;
    }

    /**
     * @return mixed|string
     */
    function getBootstrapScriptRunningInCloundInit() {
        $bootStrapFilename = __DIR__."/../../../scripts/bootstrap/bootStrap.sh";
        $bootstrapFileContents = file_get_contents($bootStrapFilename);

        if ($bootstrapFileContents === false) {
            throw new ServerContainerException("Failed to open $bootStrapFilename to build complete bootstrap.");
        }

        $clavisBuilder = $this->getClavisWritingScript();
        $bootstrapFileContents = str_replace("# %CLAVIS_WRITER%", $clavisBuilder, $bootstrapFileContents);

        $intahwebzConf = $this->getIntahwebzConfigWritingScript();
        $bootstrapFileContents = str_replace("# %INTAHWEBZ_CONF%", $intahwebzConf, $bootstrapFileContents);

        return $bootstrapFileContents;
    }


    function getIntahwebzConfigWritingScript() {

$configContents = <<< 'END'
<?php

define('HACKING', false);

define('LIVE_SERVER', TRUE);
define('SCRIPTS_VERSION', '1.9.8');

if(HACKING == false){
	define('CONTENT_BUCKET', 'content.basereality.com');
	define('BACKUP_BUCKET', 'backup.basereality.com');
}
else{
	define('CONTENT_BUCKET', 'contenttest3.basereality.com');
	define('BACKUP_BUCKET', 'backuptest3.basereality.com');
}

//This bucket is the same across all instances. It allows us to have large images in
//articles that are shown the same across all servers.
define('STATIC_BUCKET', 'static.basereality.com');

define('MYSQL_PORT', 3306);

define('MYSQL_USERNAME', 'intahwebz');
define('MYSQL_PASSWORD', 'pass123');
define('MYSQL_ROOT_PASSWORD', 'pass123');
define('JIG_RENDER_CHECK', 'COMPILE_CHECK_EXISTS');
define('MYSQL_SERVER', null);
define('MYSQL_SOCKET_CONNECTION', '/var/lib/mysql/mysql.sock');

define('CDN_CNAMES', 5);
define('CDN_ENABLED', FALSE);
define('ROOT_DOMAIN', 'basereality.test');
define('BLOG_ROOT_DOMAIN', 'blog.basereality.test');

////Intahwebz credentials
define('AWS_SERVICES_KEY', '%INTAHWEBZ_AWS_SERVICES_KEY%');
define('AWS_SERVICES_SECRET', '%INTAHWEBZ_AWS_SERVICES_SECRET%');

//Root credentials
define('FLICKR_KEY', '%FLICKR_KEY%');
define('FLICKR_SECRET', '%FLICKR_SECRET%');

define('GITHUB_ACCESS_TOKEN', '%GITHUB_ACCESS_TOKEN%');
define('GITHUB_REPO_NAME', 'Danack/intahwebz');

END;

        return $this->genConfig($configContents, "/home/intahwebz/intahwebzConf.php");
    }

    function getClavisWritingScript() {

        $clavisContents = <<< END
<?php

define('FLICKR_KEY', '%FLICKR_KEY%');
define('FLICKR_SECRET', '%FLICKR_SECRET%');

define('GITHUB_ACCESS_TOKEN', '%GITHUB_ACCESS_TOKEN%');
define('GITHUB_REPO_NAME', 'Danack/ServerContainer');

define('MYSQL_USERNAME', '%MYSQL_PASSWORD%');
define('MYSQL_PASSWORD', '%MYSQL_PASSWORD%');
define('MYSQL_ROOT_PASSWORD', '%MYSQL_ROOT_PASSWORD%');

//Server container
define('AWS_SERVICES_KEY', '%AWS_SERVICES_KEY%');
define('AWS_SERVICES_SECRET', '%AWS_SERVICES_SECRET%');

http://www.bashton.com/blog/2013/centos-6-4-ami-available/
define('AMAZON_MACHINE_IMAGE_NAME', 'ami-f261f0c8'); //Centos Sydney - not sure I trust them
define('AMAZON_EC2_INSTANCE_TYPE', 'm1.small');
define('AMAZON_EC2_SECURITY_GROUP', 'WebFrontendSecurityGroup');
define('AMAZON_EC2_SSH_KEY_PAIR_NAME', 'OzServer1');

\\\$clavisList = array(
    'GITHUB_REPO_NAME',
    'GITHUB_ACCESS_TOKEN',
    'FLICKR_KEY',
    'FLICKR_SECRET',
    'AWS_SERVICES_KEY',
    'AWS_SERVICES_SECRET',
);

END;

        return $this->genConfig($clavisContents, "/home/servercontainer/clavis.php");
 
    }

    function genConfig($configPlaceHolder, $filename) {


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

        $searchReplaceArray['%MYSQL_PASSWORD%'] = 'intahwebz';
        $searchReplaceArray['%MYSQL_PASSWORD%'] = 'intahwebz';
        $searchReplaceArray['%MYSQL_ROOT_PASSWORD%'] = 'pass123';

        $searchArray = array_keys($searchReplaceArray);
        $replaceArray = array_values($searchReplaceArray);
        $bootstrapFileContents = str_replace($searchArray, $replaceArray, $configPlaceHolder);

        $output = "\n";

        $pipe = ">";
        foreach (explode("\n", $bootstrapFileContents) as $line) {
            $output .= "echo \"$line\" $pipe $filename \n";
            $pipe = ">>";
        }

        $output .= "\n";

        return $output;

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
