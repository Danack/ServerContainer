<?php

use Configurator\ConfiguratorException;

$app_name = 'servercontainer';


$default = [
    
    'app_name' => $app_name,
    
    'mysql_casetablenames' => '0',
    'mysql_charset' => 'utf8mb4',
    'mysql_collation' => 'utf8mb4_unicode_ci',
    'mysql_datadir' => '/var/lib/mysql',
    'mysql_socket' => '/var/lib/mysql/mysql.sock',
    'mysql_log_directory' => '/var/log',
    
    'app_root_directory' => dirname(__DIR__),
    
    //'phpfpm_user' => $app_name,
    'phpfpm_group' => 'www-data',
    'phpfpm_socket_directory' => '/var/run/php-fpm',
    'phpfpm_conf_directory' => '/etc/php-fpm.d',
    'phpfpm_pid_directory' => '/var/run/php-fpm',
    'phpfpm_www_maxmemory' => '16M',

    'php_conf_directory' => '/etc/php',
    'php_log_directory' => '/var/log/php',
    'php_errorlog_directory' => '/var/log/php',
    'php_session_directory' => '/var/lib/php/session',
];


$evaluate = function ($config, $environment) {
    if (array_key_exists('app_name', $config) == false) {
        throw new ConfiguratorException("app.name isn't set for environment '$environment'.");
    }
    
    if (array_key_exists('phpfpm_socket_directory', $config) == false) {
        throw new ConfiguratorException("phpfpm_socket_directory isn't set for environment '$environment'.");
    }

    $phpfpm_socket_directory = $config['phpfpm_socket_directory'];
    $app_name = $config['app_name'];

    return [
        'phpfpm_socket_fullpath' => "$phpfpm_socket_directory/php-fpm-$app_name.sock"
    ]; 
};