<?php
$pwd = shell_exec('pwd');
$s = file_get_contents('/etc/apache2/sites-enabled/000-default.conf');
$s = str_replace('/var/www/html', $pwd, $s);
file_put_contents('000-default.conf', $s);
