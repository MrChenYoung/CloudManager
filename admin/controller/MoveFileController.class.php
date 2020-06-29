<?php

$path = $argv[1];
file_put_contents($path,"测试移动文件，参数1:".$argv[1]." 参数2:".$argv[2],FILE_APPEND);