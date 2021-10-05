<?php
# Reads the yml matrix for ci.yml and extracts the matrix and echos as json
$yml = file_get_contents('ci.yml');
$a = yaml_parse($yml);
$json = json_encode($a['jobs']['metadata']['strategy']['matrix']);
$json = preg_replace("#\n +#", "\n", $json);
$json = str_replace("\n", '', $json);
echo trim($json);
