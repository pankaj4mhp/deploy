@ECHO OFF
title deploy (github.com/midhundevasia/Deploy/)

set /p revno="Enter Revision Number: " %=%

php deploy.php -r%revno%

PAUSE