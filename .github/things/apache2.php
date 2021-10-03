<?php
$pwd = shell_exec('pwd');
$fn = '/etc/apache2/sites-enabled/000-default.conf';
$s = file_get_contents($fn);
$s = str_replace('/var/www/html', $pwd, $s);
file_put_contents($fn, $s);
