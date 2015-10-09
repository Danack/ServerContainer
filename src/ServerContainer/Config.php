<?php

namespace ServerContainer;


class Config
{
    const GITHUB_ACCESS_TOKEN = 'github.access_token';
    const GITHUB_REPO_NAME = 'github.repo_name';

    const FLICKR_KEY = "flickr.key";
    const FLICKR_SECRET = "flickr.secret";

    //Server container
    const BASEREALITY_AWS_SERVICES_KEY = 'basereality.aws.services.key';
    const BASEREALITY_AWS_SERVICES_SECRET = 'basereality.aws.services.secret';
    
    const SERVER_CONTAINER_AWS_SERVICES_KEY = 'servercontainer.aws.services.key';
    const SERVER_CONTAINER_AWS_SERVICES_SECRET = 'servercontainer.aws.services.secret';
    
    const LIBRATO_KEY = 'librato.key';
    const LIBRATO_USERNAME = 'librato.username';
    const LIBRATO_STATSSOURCENAME = 'librato.stats_source_name';

    const JIG_COMPILE_CHECK = 'jig.compilecheck';

    const S3_BACKUP_BUCKET = 'aws.s3.bucket.backup';
    const S3_CONTENT_BUCKET = 'aws.s3.bucket.content';


    private $values = [];

    public function __construct()
    {
        $this->values = [];
        //$this->values = array_merge($this->values, getAppEnv());
        $this->values = array_merge($this->values, getKeysServerContainer());
    }

    public function getKey($key)
    {
        if (array_key_exists($key, $this->values) == false) {
            throw new \Exception("Missing config value of $key");
        }

        return $this->values[$key];
    }

    public function getKeyWithDefault($key, $default)
    {
        if (array_key_exists($key, $this->values) === false) {
            return $default;
        }

        return $this->values[$key];
    }
}
