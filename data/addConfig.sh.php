<?php

$dir = ${'servercontainer.root.directory'};

$config = <<< END


rm -f /etc/my.cnf

ln -s $dir/autogen/my.cnf /etc/my.cnf

END;


return $config;
