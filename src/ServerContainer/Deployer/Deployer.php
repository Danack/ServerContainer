<?php

namespace ServerContainer\Deployer;

use GithubService\GithubArtaxService\GithubService;
use Amp\Artax\Client as ArtaxClient;
use Amp\Artax\Response;
use ServerContainer\ServerContainerException;

class Deployer {

    /**
     * @var GithubService
     */
    private $githubService;
    
    private $appConfigList;

    private $artaxClient;
    
    private $tempDirectory;
    
    
    function __construct(
        GithubService $githubService,
        ArtaxClient $artaxClient,
        $tempDirectory,
        $cacheDirectory) {
        $this->githubService = $githubService;
        $this->appConfigList = [
            ['danack', 'Imagick-demos', 'master', 'version' => 'latest' ]
        ];

        $this->artaxClient = $artaxClient;
        $this->tempDirectory = $tempDirectory;
        $this->cacheDirectory = $cacheDirectory;
    }

    /**
     * 
     */
    function run() {
        foreach ($this->appConfigList as $appConfig) {
            $author = $appConfig[0];
            $packageName = $appConfig[1];
            $commit = $this->findAppToUpdate($author, $packageName);
            if ($commit) {
                $this->downloadPackage($author, $packageName, $commit);

                $unpackedDirname = sprintf(
                    "%s-%s",
                    $packageName,
                    $commit->sha
                );

                $username = 'imagickdemo';

                $command = sprintf(
                    "sh ./scripts/deployPackage.sh %s_%s ./var/cache/%s_%s_%s.tar.gz %s %s %s",
                    $author,
                    $packageName,
                    $author,
                    $packageName,
                    $commit->sha,
                    "./scripts/deploy.sh",
                    $unpackedDirname,
                    $username
                );

                echo "need to run command: \n".$command."\n";
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
            null,
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
        $blobType = 'tar.gz'; //= 'zip';
        $downloadURL = sprintf(
            "https://github.com/%s/%s/archive/%s.%s",
            $author,
            $packageName,
            $commit->sha,
            $blobType
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
            //Already exists
            return $archiveFilename; 
        }
        
        $promise = $this->artaxClient->request($downloadURL);
        $response = \Amp\wait($promise);

        if ($response->getStatus() !== 200) {
            throw new ServerContainerException("Failed to download archive from: $downloadURL status was ".$response->getStatus());
        }

        file_put_contents($archiveFilename, $response->getBody());

        return $archiveFilename;
    }
}

