<?php

$inputs_yml = <<<TXT
run_endtoend: false
run_js: false
run_phpcoverage: true
run_phplint: false
run_phpunit: true
default_jobs:
  - php: 7.3
    endtoend: true
  - php: 7.4
    js: true
  - php: 7.3
    phpcoverage: true
  - php: 7.4
    phplinting: true
  - php: 7.4
    phpunit: true
custom_jobs:
  - php: 7.2
    phplinting: true
TXT;

$inputs = yaml_parse($inputs_yml);
$run_tests = [];
$default_jobs = [];
$custom_jobs = [];
foreach ($inputs as $input => $value) {
    if (preg_match('#^run_#', $input)) {
        if ($value === 'true') $value = true;
        if ($value === 'false') $value = false;
        var_dump($value);
        $test = str_replace('run_', '', $input); // e.g. run_phplinting => phplinting
        $run_tests[$test] = $value;
    } else if ($input == 'custom_jobs') {
        $custom_jobs = $value;
    } else if ($input == 'default_jobs') {
        $default_jobs = $value;
    }
}

$new_matrix = ['include' => []];
foreach ($default_jobs as $arr) {
    foreach (array_keys($arr) as $test) {
        if ($test == 'php' || !isset($run_tests[$test]) || !$run_tests[$test]) continue;
        $new_matrix['include'][] = $arr;
    }
}
foreach ($custom_jobs as $arr) {
    $new_matrix['include'][] = $arr;
}

$json = json_encode($new_matrix);
$json = preg_replace("#\n +#", "\n", $json);
$json = str_replace("\n", '', $json);
echo trim($json);