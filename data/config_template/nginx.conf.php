<?php

$config = <<< END


server {
    listen      80;
    server_name opcache.phpimagick.com opcache.phpimagick.test;
    root        ${'app_root_directory'}/vendor/peehaa/OpcacheGUI/public;

    # This is only needed when using URL paths

    #enable rewrite log which spams the error log.
    #rewrite_log on;

    access_log  /var/log/nginx/${'app_name'}.access.log requestTime;    
    error_log  /var/log/nginx/${'app_name'}.error.log;

    location ~* \.php$ {
        set \$originalURI  \$uri;
        try_files \$uri /index.php /50x_static.html;
        fastcgi_param  QUERY_STRING  q=\$originalURI&\$query_string;
        fastcgi_pass   unix:${'phpfpm_socket_fullpath'};
        include       ${'app_root_directory'}/data/config_template/fastcgi.conf;
    }
}


END;

return $config;
