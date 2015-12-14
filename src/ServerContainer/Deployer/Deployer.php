<?php

namespace ServerContainer\Deployer;

use GithubService\GithubArtaxService\GithubService;
use Amp\Artax\Client as ArtaxClient;
use Amp\Artax\Response;
use ArtaxServiceBuilder\Oauth2Token;
use ServerContainer\MessageException;
use ServerContainer\ServerContainerException;

class Deployer
{
    /**
     * @var GithubService
     */
    private $githubService;
    
    private $appConfigList;

    private $artaxClient;
    
    private $tempDirectory;

    /**
     * @var Oauth2Token
     */
    private $oauthToken;
    
    function __construct(
        GithubService $githubService,
        ArtaxClient $artaxClient,
        Oauth2Token $oauthToken,
        $tempDirectory,
        $cacheDirectory) {
        $this->githubService = $githubService;
        $this->appConfigList = [
            'blog'    => [ 'danack', 'blog', 'master', 'version' => 'latest' ],
            'imagickdemos' => [ 'danack', 'Imagick-demos', 'master', 'version' => 'latest' ],
            'basereality'    => [ 'danack', 'intahwebz', 'master', 'version' => 'latest' ],
            'intahwebz'    => [ 'danack', 'intahwebz_com', 'master', 'version' => 'latest' ],
            'tierjigdocs'    => [ 'danack', 'tierjigdocs', 'master', 'version' => 'latest' ],
        ];

        $this->artaxClient = $artaxClient;
        $this->tempDirectory = $tempDirectory;
        $this->cacheDirectory = $cacheDirectory;
        $this->oauthToken = $oauthToken;
    }

    
    /**
     * 
     */
    function run($application) {
        $apps = array_keys($this->appConfigList);
        $appsString = implode(", ", $apps);
        $appsString .= " or 'all'.";

        if (strlen($application) == null) {
            throw new MessageException("Please specify the application to deploy, one of ".$appsString);
        }

        if ($application === 'all') {
            //allowed
        }
        else if (array_key_exists($application, $this->appConfigList) == false) {
            throw new MessageException("Unknown application '$application', please choose one of $appsString");
        }

        foreach ($this->appConfigList as $projectName => $appConfig) {
            if ($application === 'all' || $projectName === $application) {
                set_time_limit(500);
                $author = $appConfig[0];
                $packageName = $appConfig[1];
                $keys = getKeysServerContainer("environment");
                
                if (array_key_exists("environment", $keys) == false) {
                    throw new ServerContainerException('Environment not set in keys file'); 
                }
                
                $environment = $keys["environment"];

                $commit = $this->findAppToUpdate($author, $packageName);
                if ($commit) {
                    $archiveFilename = $this->downloadPackage($author, $packageName, $commit);
                    $command = sprintf(
                        "sh ./scripts/deploy/deployPackage.sh %s %s %s %s %s",
                        $projectName,
                        $commit->sha,
                        $archiveFilename,
                        $packageName,
                        $environment
                    );

                    echo "need to run command: \n".$command."\n";
                    system($command);
                }
            }
        }
    }

    /**
     * @param $author
     * @param $packageName
     * @return \GithubService\Model\Commit
     */
    function findAppToUpdate($author, $packageName) {
        $operation = $this->githubService->listRepoCommits(
            $this->oauthToken,
            $author,
            $packageName
        );

        $commitList = $operation->execute();

        foreach ($commitList as $commit) {
            return $commit;
        }
        return null;
    }

    /**
     * @param $author
     * @param $packageName
     * @param \GithubService\Model\Commit $commit
     */
    function downloadPackage($author, $packageName, \GithubService\Model\Commit $commit) {
        $blobType = 'tar.gz';

        $archiveOperation = $this->githubService->getArchiveLink(
            $this->oauthToken,
            $author,
            $packageName,
            $commit->sha
        );
        
        /** @var $response Response */
        $archiveFilename = sprintf(
            "%s/%s_%s_%s.%s",
            $this->cacheDirectory,
            $author,
            $packageName,
            $commit->sha,
            $blobType
        );

        if (file_exists($archiveFilename)) {
            //throw new ServerContainerException("Archive file $archiveFilename already exists. Cannot download over it.");
            return $archiveFilename; //Already exists 
        }

        $filebody = $archiveOperation->execute();
        file_put_contents($archiveFilename, $filebody);

        return $archiveFilename;
    }
}

