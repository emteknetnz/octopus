<?php
$pwd = shell_exec('pwd');
$a = file_get_contents('.github/things/docker_apache_default');
$a = str_replace('/var/www', $pwd, $a);
file_put_contents('/etc/apache2/sites-enabled/000-default.conf', $a);
