<?php
# Reads the yml matrix for ci.yml and inputs.txt and creates a new json matrix
$yml = file_get_contents('ci.yml');
$y = yaml_parse($yml);
$matrix = $y['jobs']['metadata']['strategy']['matrix'];
$includes = [];
foreach (explode("\n", file_get_contents('inputs.txt')) as $line) {
    if (empty($line)) continue;
    list($input, $do_include) = preg_split('#=#', $line);
    $do_include = $do_include == 'true';
    $test = str_replace('run_', '', $input); // e.g. run_phplint => phplint
    $includes[$test] = $do_include; 
}

$new_matrix = ['include' => []];
foreach ($matrix['include'] as $arr) {
    foreach (array_keys($arr) as $test) {
        if ($test == 'php' || !isset($includes[$test]) || !$includes[$test]) continue;
        $new_matrix['include'][] = $arr;
    }
}

$json = json_encode($new_matrix);
$json = preg_replace("#\n +#", "\n", $json);
$json = str_replace("\n", '', $json);
echo trim($json);
