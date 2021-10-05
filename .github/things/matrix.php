<?php
# Reads the yml matrix for ci.yml and inputs.txt and creates a new json matrix
$yml = file_get_contents('ci.yml');
// fix really weird bug in yaml_parse where 'on' key is turned into '1'
$yml = str_replace("\non:", "\nonx:", $yml);
$y = yaml_parse($yml);
$ci_matrix = $y['jobs']['metadata']['strategy']['matrix']['include'];
$ci_inputs = $y['onx']['workflow_call']['inputs'];
$module_inputs = [];
foreach (explode("\n", file_get_contents('module_inputs.txt')) as $line) {
    list($input, $b) = preg_split('#=#', $line);
    $module_inputs[$input] = $b;
}

$tests = [];
foreach ($ci_inputs as $input => $arr) {
    $include = $arr['default'];
    if (isset($module_inputs[$input])) {
        $include = $module_inputs[$input];
    }
    if ($include) {
        $test = str_replace('run_', '', $input); // e.g. run_phplint => phplint
        $tests[$test] = true; 
    }
}

$out = ['jobs'=>['metadata'=>['strategy'=>['matrix'=>['include']]]]];

foreach ($ci_matrix['include'] as $arr) {
    foreach (array_keys($arr) as $test) {
        if ($test == 'php') {
            continue;
        }
        if (isset($tests[$test])) {
            $out['jobs']['metadata']['strategy']['matrix']['include'][] = $arr;
        }
    }
}

$json = json_encode($out);
$json = preg_replace("#\n +#", "\n", $json);
$json = str_replace("\n", '', $json);
echo trim($json);
