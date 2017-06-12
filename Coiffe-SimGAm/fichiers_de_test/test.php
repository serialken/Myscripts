<?php
/**
 * Created by PhpStorm.
 * User: ydieng
 * Date: 05/05/2017
 * Time: 16:32
 */
$fullPath = $argv[1];
$myfile = fopen($fullPath,'r+');
fseek($myfile, 469,SEEK_CUR);
//fseek($myfile, 152,SEEK_CUR);
fputs($myfile, "kdhstfhe", 8);
//fseek($myfile, 152);
fputs($myfile, "test", 8);
fclose($myfile);