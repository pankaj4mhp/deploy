@ECHO OFF
title deploy (github.com/midhundevasia/deploy/)

set /p revno="Enter Revision Number: " %=%

php deploy.php -r%revno%

PAUSE
