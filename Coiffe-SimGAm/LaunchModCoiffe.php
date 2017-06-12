<?php
/**
 * User: ydieng
 * Script permettant de lister un repertoire et de lancer un script donné
 */

$launcherName = "LaunchModCoiffe.php";
$scriptName = "ModCoiffe.php";
$verbose = true;
$dateFormat = 'ymdHi';
$currentDate = date($dateFormat);
$fullPathDir = $argv[1];
mb_internal_encoding("ISO-8859-1");

if (is_dir($fullPathDir)){
    $files = scandir($fullPathDir);
    $nbFiles = count($files)-2;
    $ctFiles = count($files)-1;
    $scriptExecuted = 0;
    echo "il y a $nbFiles fichier(s) dans le repertoire .\n";
    if (($fullPathDir[(strlen($fullPathDir))-1]) == "\\"){
        $completeFullpath = $fullPathDir;
    }else{
        $completeFullpath = $fullPathDir ."\\";
    }
//    $tt = ($files[0] == ".");
//    var_dump( $tt);
    for($i = 0; $i <= $ctFiles; $i++){
        if(($files[$i] !== ".") && ($files[$i] !== "..")){
            $path = $completeFullpath . $files[$i];
            echo"execution de $files[$i] ..................\n";
            `php $scriptName $path`;
            $scriptExecuted++;
        }
        unset($path);
    }
    echo "le script s'est execute sur $scriptExecuted fichier(s)";
//    var_dump($completeFullpath);
}else{
    echo " '$fullPathDir' n'est pas un dossier. \n";
}