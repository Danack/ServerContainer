<?php

$default = [
    'mysql.casetablenames' => '0',
    'mysql.charset' => 'utf8mb4',
    'mysql.collation' => 'utf8mb4_unicode_ci',
    'mysql.datadir' => '/var/lib/mysql',
    'mysql.socket' => '/var/lib/mysql/mysql.sock',
    'mysql.log.directory' => '/var/log',
    'servercontainer.root.directory' => '/home/ServerContainer',
];

$centos_guest = [
    'servercontainer.root.directory' => '/home/github/ServerContainer/ServerContainer',
];

$amazonec2 = [
    'servercontainer.root.directory' => '/home/servercontainer/servercontainer',
];