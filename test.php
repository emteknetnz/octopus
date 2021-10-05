<?php

$yml = <<<YML
jobs:
  tests:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:5.7
        env:
          MYSQL_HOST: 127.0.0.1
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: SS_mysite
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    needs: genmatrix
    strategy:
      matrix: \${{fromJson(needs.genmatrix.outputs.matrix)}}
      # set fail-fast to false prevent one job from cancelling other jobs
      fail-fast: false
      matrix:
        include:
      #     # behat test works - TODO: switch to serve.php TODO: change to endtoend for abstraction
      #     # - php: '7.3'
      #     #   behat: true
      #     # npm test probably works, though needs to be tested on a proper repo
      #     # - php: '7.4'
      #     #   npm: true
          - php: '7.4'
            phpunit: true
      #     # phpunit coverage test works
      #     # phpcoverage limited to php7.3 due to old phpunit5 otherwise you'll get Class 'PHP_Token_COALESCE_EQUAL' not found
      #     # - php: '7.3'
      #     #   phpcoverage: true
          - php: '7.4'
            phplint: true
      #     # phpstan probably works though needs to be tested on something that actually uses it
      #     # - php: '7.4'
      #     #   phpstan: true
YML;
$a = yaml_parse($yml);
echo json_encode($a['jobs']['tests']['strategy']['matrix'], JSON_PRETTY_PRINT);
