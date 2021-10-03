<?php

print_r(
    [
        'whoai' => shell_exec('whoami'),
        'id' => shell_exec('id'),
        'processUser' => posix_getpwuid(posix_geteuid())
    ]
);
