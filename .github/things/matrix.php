<?php
# Reads the yml matrix for ci.yml and inputs.txt and creates a new json matrix
$yml = file_get_contents('ci.yml');
$y = yaml_parse($yml);
$matrix = $y['jobs']['metadata']['strategy']['matrix']['include'];
$includes = [];
foreach (explode("\n", file_get_contents('inputs.txt')) as $line) {
    if (empty($line)) continue;
    list($input, $do_include) = preg_split('#=#', $line);
    if (!$do_include) continue;
    $test = str_replace('run_', '', $input); // e.g. run_phplint => phplint
    $includes[$test] = true; 
}

$out = ['jobs'=>['metadata'=>['strategy'=>['matrix'=>['include']]]]];

foreach ($matrix as $arr) {
    foreach (array_keys($arr) as $test) {
        if ($test == 'php') continue;
        if (isset($includes[$test]) && $includes[$test]) {
            $out['jobs']['metadata']['strategy']['matrix']['include'][] = $arr;
        }
    }
}

$json = json_encode($out);
$json = preg_replace("#\n +#", "\n", $json);
$json = str_replace("\n", '', $json);
echo trim($json);
