<?php

$myfile = fopen("/tmp/sess_gek01js8pibd086bj5jlkvu6a0", "r");
echo unserialize(fread($myfile,filesize("/tmp/sess_gek01js8pibd086bj5jlkvu6a0")));
fclose($myfile);

?>