#!/bin/sh

php -c "$(dirname "$0")/php.ini" "$(dirname "$0")/bin/compile.php"
