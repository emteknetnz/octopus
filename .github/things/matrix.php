<?php

# parse ci.yml
# get jobs.tests.strategy.matrix
# do something
$json = <<<JSON
    {
        "include":[
            {
                "php":"7.4",
                "phpunit":true
            },
            {
                "php":"7.4",
                "phplint":true
            }
        ]
    }
JSON;
$json = preg_replace("#\n +#", "\n", $json);
$json = str_replace("\n", '', $json);
echo trim($json);
