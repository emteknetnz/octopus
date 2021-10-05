<?php

$yml = <<<YML
# https://docs.github.com/en/actions/learn-github-actions/workflow-syntax-for-github-actions
# https://docs.github.com/en/actions/learn-github-actions/contexts#context-availability
# https://docs.github.com/en/actions/learn-github-actions/reusing-workflows

name: ci

on:
  workflow_call:
    inputs:
      composer_require_extra:
        type: string
        required: false
        default: ''
      run_endtoend:
        type: boolean
        #description: "behat"
        required: false
        default: false
      run_js:
        type: boolean
        #description: "yarn build diff, yarn lint, yarn test"
        required: false
        default: false
      run_phpcoverage:
        type: boolean
        #description: "codecov"
        required: false
        default: true
      run_phplint:
        type: boolean
        #description: "phpcs, phpstan (optional), cow schema validate"
        required: false
        default: true
      run_phpunit:
        type: boolean
        #descrtiption: "phpunit"
        required: false
        default: true

jobs:

  metadata:
    runs-on: ubuntu-latest
    if: ABC{{ github.action == 'never' }}
    steps:
      - run: |
         # Do nothing
    strategy:
      matrix:
        include:
          # phpcoverage limited to php7.3 due to old phpunit5 otherwise you'll get Class 'PHP_Token_COALESCE_EQUAL' not found
          - php: '7.3'
            endtoend: true
          - php: '7.4'
            js: true
          - php: '7.3'
            phpcoverage: true
          - php: '7.4'
            phplint: true
          - php: '7.4'
            phpunit: true

  # used to generate a dynamic jobs matrix
  genmatrix:
    runs-on: ubuntu-latest
    outputs:
      matrix: ABC{{ steps.generate-matrix.outputs.matrix }}
    steps:
      # not sure why I'd need checkout, try deleting later
      - name: Checkout code
        uses: actions/checkout@v2
      - name: Install PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '7.4'
          extensions: yaml
      - name: generate matrix
        id: generate-matrix
        run: |
          wget https://raw.githubusercontent.com/emteknetnz/octopus/main/.github/things/matrix.php
          wget https://raw.githubusercontent.com/emteknetnz/octopus/main/.github/workflows/ci.yml
          echo module_inputs.txt
          touch module_inputs.txt
          echo "run_endtoend=ABC{{ inputs.run_endtoend }}" >> module_inputs.txt
          echo "run_js=ABC{{ inputs.run_js }}" >> module_inputs.txt
          echo "run_phpcoverage=ABC{{ inputs.run_phpcoverage }}" >> module_inputs.txt
          echo "run_phplint=ABC{{ inputs.run_phplint }}" >> module_inputs.txt
          echo "run_phpunit=ABC{{ inputs.run_phpunit }}" >> module_inputs.txt
          MATRIX_JSON=ABC(php matrix.php)
          echo "::set-output name=matrix::ABC{MATRIX_JSON}"

  tests:
    # TODO: check if this is an LTS - should be 20.04
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
      matrix: ABC{{fromJson(needs.genmatrix.outputs.matrix)}}
      # set fail-fast to false prevent one job from cancelling other jobs
      fail-fast: false
      # matrix:
      #   include:
      #     # behat test works - TODO: switch to serve.php TODO: change to endtoend for abstraction
      #     # - php: '7.3'
      #     #   behat: true
      #     # npm test probably works, though needs to be tested on a proper repo
      #     # - php: '7.4'
      #     #   npm: true
      #     - php: '7.4'
      #       phpunit: true
      #     # phpunit coverage test works
      #     # phpcoverage limited to php7.3 due to old phpunit5 otherwise you'll get Class 'PHP_Token_COALESCE_EQUAL' not found
      #     # - php: '7.3'
      #     #   phpcoverage: true
      #     - php: '7.4'
      #       phplint: true
      #     # phpstan probably works though needs to be tested on something that actually uses it
      #     # - php: '7.4'
      #     #   phpstan: true

    # TODO:
    # - [ ] provision as inputs? e.g. self (no jobs) how would that work?
    # - [ ] custom jobs matrix in inputs
    # (kind of seems like I'll need the json matrix)
    # - [ ] Artifacts aren't working right when in reusing jobs context
    # - [ ] composer REQUIRE_GRAPHQL, etc
    # - [ ] matrix entry for graphql3 vs 4 for behat
    # - [ ] PDO matrix entry
    # - [ ] Probably get rid of apache and use serve.php for behat since it'll run the job faster and it simplies this file (though behat will always be slow)

    name: PHP ABC{{ matrix.php }}ABC{{ matrix.phpunit && ' - phpunit' || '' }}ABC{{ matrix.endtoend && ' - endtoend' || '' }}ABC{{ matrix.js && ' - js' || '' }}ABC{{ matrix.phpcoverage && ' - phpcoverage' || '' }}ABC{{ matrix.phplint && ' - phplint' || '' }}

    steps:
      - name: Preparation
        run: |
          mkdir artifacts
      
      - name: Checkout code
        uses: actions/checkout@v2

      - name: Install PHP - regular
        uses: shivammathur/setup-php@v2
        if: ABC{{ matrix.behat != true && matrix.phpcoverage != true }}
        with:
          php-version: ABC{{ matrix.php }}
          extensions: curl, dom, gd, intl, json, ldap, mbstring, mysql, tidy, zip
          tools: composer:v2

      - name: Install PHP - phpunit coverage test
        uses: shivammathur/setup-php@v2
        if: ABC{{ matrix.phpcoverage == true && inputs.run_phpcoverage }}
        with:
          php-version: ABC{{ matrix.php }}
          extensions: curl, dom, gd, intl, json, ldap, mbstring, mysql, tidy, xdebug, zip
          tools: composer:v2
          coverage: xdebug

      # TODO: probably don't need apache, just revert to using serve.php for behat
      # would need to check if cms installed?  it's a different bootstrap to what's in behat.yml
      # For behat, install php manually
      # This is kind of slow at around 44 seconds, it's only used because there are
      # issues apt installing libapache2-mod-php which is needed for behat
      - name: Install PHP - behat test
        if: ABC{{ matrix.endtoend == true  && inputs.run_endtoend }}
        run: |
          # Make all php versions other than the current main version available
          sudo apt update && sudo sudo apt install -y software-properties-common
          sudo add-apt-repository -y ppa:ondrej/php
          sudo add-apt-repository -y ppa:ondrej/apache2
          sudo apt update
          sudo apt install libapache2-mod-phpABC{{ matrix.php }} phpABC{{ matrix.php }} phpABC{{ matrix.php }}-cli phpABC{{ matrix.php }}-curl  phpABC{{ matrix.php }}-dom phpABC{{ matrix.php }}-gd phpABC{{ matrix.php }}-intl phpABC{{ matrix.php }}-json phpABC{{ matrix.php }}-ldap phpABC{{ matrix.php }}-mbstring phpABC{{ matrix.php }}-mysql phpABC{{ matrix.php }}-tidy phpABC{{ matrix.php }}-xdebug phpABC{{ matrix.php }}-zip tidy
          # This this verison of PHP instead of the default ubuntu php8.0 install
          sudo rm /etc/alternatives/php
          sudo ln -s /usr/bin/phpABC{{ matrix.php }} /etc/alternatives/php
          # Install composer
          php -r "copy('https://composer.github.io/installer.sig', 'installer.sig');"
          php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
          php -r "if (hash_file('sha384', 'composer-setup.php') === file_get_contents('installer.sig')) { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
          if [ -f composer-setup.php ]; then php composer-setup.php && rm composer-setup.php; fi
          rm installer.sig
          sudo mv composer.phar /usr/local/bin/composer

      - name: Configure PHP
        run: |
          if [ ! ABC(which php) ]; then echo "PHP not installed, skipping" && exit 0; fi
          sudo sh -c "echo 'memory_limit = 8196M' >> /etc/php/ABC{{ matrix.php }}/cli/php.ini"
          if [ -f /etc/php/ABC{{ matrix.php }}/apache2/php.ini ]; then
            sudo sh -c "echo 'memory_limit = 8196M' >> /etc/php/ABC{{ matrix.php }}/apache2/php.ini"
          fi
          echo "PHP has been configured"

      - name: Configure apache - behat test
        if: ABC{{ matrix.endtoend == true  && inputs.run_endtoend }}
        run: |
          # apache2 is installed and running by default in ubuntu
          # update dir.conf to use index.php as the primary index doc
          wget https://raw.githubusercontent.com/emteknetnz/octopus/main/.github/things/dir.conf
          sudo cp dir.conf /etc/apache2/mods-enabled/dir.conf
          rm dir.conf
          # this script will create a 000-default.conf file with the pwd as the DocumentRoot
          wget https://raw.githubusercontent.com/emteknetnz/octopus/main/.github/things/000-default.conf
          wget https://raw.githubusercontent.com/emteknetnz/octopus/main/.github/things/apache2.php
          php apache2.php
          rm apache2.php
          rm 000-default.conf
          sudo cp 000-default.conf /etc/apache2/sites-enabled/000-default.conf
          sudo a2enmod rewrite
          # run apache as 'runner:docker' instead of 'www-data:www-data'
          sudo sh -c "echo 'export APACHE_RUN_USER=runner' >> /etc/apache2/envvars"
          sudo sh -c "echo 'export APACHE_RUN_GROUP=docker' >> /etc/apache2/envvars"
          sudo systemctl restart apache2
          echo "Apache has been configured"

      - name: Composer
        run: |
          if [ ! ABC(which php) ]; then echo "PHP not installed, skipping" && exit 0; fi
          # Update composer.json and install dependencies
          # github.base_ref is only available on pull-requests and is the target branch
          # github.ref is used for regular branch builds such as crons
          BRANCH=ABC(php -r "echo preg_replace('#^.+/#', '', 'ABC{{ github.base_ref }}'?:'ABC{{ github.ref }}');")
          if [[ "ABCBRANCH" =~ ^[1-9]ABC ]] || [[ "ABCBRANCH" =~ ^[1-9]\.[0-9]+ABC ]]; then export COMPOSER_ROOT_VERSION="ABC{BRANCH}.x-dev"; elif [[ "ABCBRANCH" =~ ^[1-9]\.[0-9]+\.[0-9]+ ]]; then export COMPOSER_ROOT_VERSION="ABC{BRANCH}"; else export COMPOSER_ROOT_VERSION="dev-ABC{BRANCH}"; fi
          echo "COMPOSER_ROOT_VERSION is ABCCOMPOSER_ROOT_VERSION"
          composer require silverstripe/installer:4.9.x-dev --no-update --prefer-dist
          composer require silverstripe/recipe-testing:^1 --no-update --prefer-dist
          if [ ABC{{ matrix.phplint }} ]; then
            composer require silverstripe/cow:dev-master --no-update --prefer-dist
          fi
          if [ "ABC{{ inputs.require_extra }}" != "" ]; then
            composer require "ABC{{ inputs.require_extra }}" --no-update --prefer-dist
          fi
          cp composer.json artifacts
          composer update --prefer-dist --no-interaction --no-progress
          cp composer.lock artifacts

      - name: Prepare Silverstripe
        run: |
          # Add .env file
          wget https://raw.githubusercontent.com/emteknetnz/octopus/main/.github/things/.env
          # Surprisingly we probably don't want to dev/build flush here
          # vendor/bin/sake dev/build flush=1
          # I was having issues with a unit test getting this issue
          # Identifier name 'SilverStripe_CampaignAdmin_Tests_AddToCampaignValidatorTest_TestObject' is too long
          # Which is possibly to do with the /tmp/silverstripe-cache-php7.4.xyz dir being out of sync with TestOnly objects
          # If we really need to dev/build flush here (perhaps just for visibility) then we could delete the silverstripe-cache dir afterwards with
          # dir=\ABC(ls /tmp | grep silverstripe-cache | head -1); if [ "\ABCdir" ]; then rm -rf /tmp/\ABCdir; fi

      - name: phpunit
        if: ABC{{ matrix.phpunit == true && inputs.run_phpunit }}
        run: |
          vendor/bin/phpunit --verbose
          echo "Passed"

      - name: behat
        if: ABC{{ matrix.endtoend == true && inputs.run_endtoend }}
        run: |
          # Run behat tests
          if [ ! -f behat.yml ]; then echo "behat.yml missing" && exit 1; fi
          # this script will update behat.yml to work with headless chrome
          wget https://raw.githubusercontent.com/emteknetnz/octopus/main/.github/things/behat-headless.yml
          wget https://raw.githubusercontent.com/emteknetnz/octopus/main/.github/things/behat.php
          php behat.php
          rm behat.php
          rm behat-headless.yml
          nohup sh -c "chromedriver --log-path=artifacts/chromedriver.log --log-level=INFO" > /dev/null 2>&1 &
          vendor/bin/behat octopus
          echo "Passed"

      - name: js
        if: ABC{{ matrix.js == true && inputs.run_js }}
        run: |
          # Run yarn test etc
          if [ ! -f package.json ]; then echo "package.json missing" && exit 1; fi
          wget https://raw.githubusercontent.com/nvm-sh/nvm/v0.35.3/install.sh
          php -r "if (hash_file('sha384', 'install.sh') === 'dd4b116a7452fc3bb8c0e410ceac27e19b0ba0f900fe2f91818a95c12e92130fdfb8170fec170b9fb006d316f6386f2b') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('install.sh'); } echo PHP_EOL;"
          if [ ! -f install.sh ]; then echo "Cannot install nvm" && exit 1; fi
          . install.sh
          rm install.sh
          export NVM_DIR="ABCHOME/.nvm"
          # this loads nvm into the current terminal
          [ -s "ABCNVM_DIR/nvm.sh" ] && \. "ABCNVM_DIR/nvm.sh"
          if [ ! -f .nvmrc ]; then echo "Missing .nvmrc" && exit 1; fi
          nvm install
          nvm use
          rm -rf client/dist
          npm install -g yarn
          yarn install --network-concurrency 1
          if [ -d vendor/silverstripe/admin ]; then
            cd vendor/silverstripe/admin
            yarn install --network-concurrency 1
            cd ../../..
          fi
          yarn run build
          git diff-files --quiet -w --relative=client
          git diff --name-status --relative=client
          yarn run test
          yarn run lint
          echo "Passed"

      - name: phplint
        if: ABC{{ matrix.phplint == true && inputs.run_phplint }}
        run: |
          if [ ! -f phpcs.xml.dist ]; then echo "Missing phpcs.xml.dist" && exit 1; fi
          vendor/bin/phpcs
          # phpstan is optional
          if [ -f phpstan.neon.dist ]; then
            vendor/bin/phpstan analyse
          fi
          # cow validation is also done here due to it being a tiny piece of work not meriting its own job
          if [ -f .cow.json ]; then
            vendor/bin/cow schema:validate
          fi
          echo "Passed"

      - name: phpcoverage
        if: ABC{{ matrix.phpcoverage == true && inputs.run_phpcoverage }}
        run: |
          curl https://keybase.io/codecovsecurity/pgp_keys.asc | gpg --import
          curl -Os https://uploader.codecov.io/latest/codecov-linux
          curl -Os https://uploader.codecov.io/latest/codecov-linux.SHA256SUM
          curl -Os https://uploader.codecov.io/latest/codecov-linux.SHA256SUM.sig
          gpg --verify codecov-linux.SHA256SUM.sig codecov-linux.SHA256SUM
          shasum -a 256 -c codecov-linux.SHA256SUM
          chmod +x codecov-linux
          phpdbg -qrr vendor/bin/phpunit --coverage-clover=coverage.xml
          # TODO: uncomment so that it uploads
          # ./codecov-linux -f coverage.xml;
          # echo "coverage.xml generated and uploaded to codecov"

      - name: Upload artifacts
        uses: actions/upload-artifact@v2
        if: always()
        with:
          name: artifacts
          path: artifacts

YML;

$inputs = <<<TXT
run_endtoend=false
run_js=false
run_phpcoverage=true
run_phplint=false
run_phpunit=true
TXT;

$yml = str_replace("\non:", "\nonx:", $yml);
$y = yaml_parse($yml);
$matrix = $y['jobs']['metadata']['strategy']['matrix'];
$includes = [];
foreach (explode("\n", $inputs) as $line) {
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
