#!/bin/bash

# this is a simple starte script for test development
# it allows to run single classes or even tests so you don't need to run the complete testing suite
# for real testing use phpunit.php

LOGFILE="phpunit/phpunit_log.htm"
OUTFILE="phpunit/phpunit_output.htm"

rm -f phpunit/*.htm

# debug e.g. shows the tests to be run
DEBUG=" --debug"
DEBUG=""

PHPUNIT="/usr/bin/phpunit"

OPTS=""
OPTS=" --testdox-html $LOGFILE --colors --stop-on-failure --bootstrap /var/www/nmsprime/bootstrap/autoload.php"

# if empty: run all tests
# can be used on developing tests (you don't want to run the complete test suite in this case)
TESTS=""
TESTS=" modules/ProvVoip/Tests/PhonenumberLifecycleTest.php"
TESTS=" --filter testIndexViewDatatablesDataAvailable modules/ProvBase/Tests/ModemLifecycleTest.php"
TESTS=" --filter testDeleteFromIndexView modules/ProvBase/Tests/ModemLifecycleTest.php"
TESTS=" --filter testRoutesAuthMiddleware tests/RoutesAuthTest"


touch $LOGFILE $OUTFILE
chmod 666 $LOGFILE $OUTFILE

CMD="sudo -u apache $PHPUNIT$OPTS$DEBUG$TESTS | tee $OUTFILE"

clear
echo
echo $CMD
echo
eval $CMD

echo
echo
echo "–––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––"
echo
echo "Log data can be found in $LOGFILE"
echo "PHPUnit output can be found in $OUTFILE"
echo
