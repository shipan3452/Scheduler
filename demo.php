<?php
$c=popen("php test.php",'r');
echo fread($c,1440);
pclose($c);