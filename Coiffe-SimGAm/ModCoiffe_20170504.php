<?php

/* Script permettant la modification des coiffe */


/**
 * Paramétrages
 */
$scriptName = "ModCoiffe_20170504.php";
$currentDate = date('ymdHi');
$verbose = true;
$fullPath = $argv[1];
$fileName = basename($fullPath);
$backupDir= "C:\\Workspace\\Scripts\\Backup"; // emplacement des fichiers de backup
$backupfileName = getBackupfileName($fileName, $currentDate);
$codeNilPath = "C:\\Workspace\\Scripts\\FichierABC_test\\Code_Nil.txt"; // emplacement du fichier contenant les codes Nil


/**
 * Archivage du document source
 */
$fulPathBackup = $backupDir."\\".$backupfileName;
$tabFile = getTabFile($fullPath);
//$tabFile = file($fullPath);
//var_dump($tabFile[34]);
//var_dump(iconv("UTF-8","ANSI",$tabFile[34]));
//var_dump(strlen($tabFile[34]));
//var_dump(iconv_get_encoding('all'));
//die();
//$resBackup = backupFile($fulPathBackup,$tabFile);
$resBackup = copyFile($fullPath,$fulPathBackup);


/** Récuperation des arguments */
$line= array();
$nblines= substr_count(file_get_contents($fullPath), "\n");
$nblinesNil= substr_count(file_get_contents($codeNilPath), "\n");
$nblinesMod = 0;
$tabCodenil = array();


/**
 * Preparation et extraction  des codes Nil
 */
$myfileNil = fopen($codeNilPath,'r');
$tabCodenil = getTabNil($myfileNil,$nblinesNil);
fclose($myfileNil);

if ($tabCodenil !== false){
    /**
     * Traitement du fichier
     */

//    var_dump($currentDate);
//    var_dump($fulPathBackup);
//    var_dump(count($tabFile));
//    var_dump($tabFile[421]);
//    die();
    $myfile = fopen($fullPath,'w+');
//    fgets($myfile)
    if ($myfile){

        echo "-----------------------Beginning of the File -------------------------\n";
        for($i = 0; $i <= $nblines; $i++){
            // on test la derniére ligne du fichier parceque c'est une ligne vide il ne faut pas faire d'opération sur cette derniére
//            var_dump($i);
            if($i != $nblines){
                $date = substr($tabFile[$i], 0, 8);
                $codeNil = substr($tabFile[$i], 8,7);
                $endline = substr($tabFile[$i],188,275);
                fputs($myfile,$tabFile[$i],152);
                fputs($myfile,($date." ".$codeNil), 16);
                if (array_key_exists($codeNil, $tabCodenil)){
                    $lenNil = strlen($tabCodenil[$codeNil]);
                    fputs($myfile,"          NIL ".str_pad($tabCodenil[$codeNil],6," "),20);
//                    $res = fputs($myfile,"          NIL ".str_pad($tabCodenil[$codeNil],6," "),20);
//                    var_dump($res);
//                    var_dump(str_pad($tabCodenil[$codeNil],6," "));
//                if ($lenNil < 6){
//                    for($j = 1;$j <= (6-$lenNil);$j++){
//                        fputs($myfile," ", 1);
//                    }
//                }
                    $nblinesMod++;
                }else{
                    fputs($myfile,"                    ",20);
                }
                fputs($myfile,$endline, 275);
                fputs($myfile,"\r\n", 2);
                unset($date, $codeNil,$endline,$lenNil);
            }
        }
//        var_dump($tabFile[35]);
//        var_dump(strlen(utf8_decode($tabFile[35])));
        echo "-----------------------End of File -------------------------\n";
    }
    else{
        echo "\n l'ouverture du fichier source n'a pas fonctionné.";
    }
    fclose($myfile);
}else{
    echo "-----------le traitement du fichier a été interompu aucune action n'a été effectué------------------------- ---------\n";
}




/**
 * Affichage des différentes variables
 * mode verbose true/false
 */
if ($verbose){
    echo "\n-----------current date------Format YYMMDDHH:MI---------------------\n";
    echo $currentDate;
    echo "\n";
    echo "-------------Full Path------------------------------------------------\n";
    echo $fullPath;
    echo "\n";
    echo "-------------File name------------------------------------------------\n";
    echo $fileName;
    echo "\n";
    echo "-------------Number of line src---------------------------------------\n";
    echo $nblines;
    echo "\n";
    echo "-------------code Nil Path--------------------------------------------\n";
    echo $codeNilPath;
    echo "\n";
    echo "-------------Number of line Code nil----------------------------------\n";
    echo $nblinesNil;
    echo "\n";
    echo "-------------Backup Directory----------------------------------------------\n";
    echo $backupDir;
    echo "\n";
    echo "-------------Backup file name------------------------------------------------\n";
    echo $backupfileName;
    echo "\n";
    echo "-------------Number of line modified ---------------------------------\n";
    echo $nblinesMod;
    echo "\n";
    echo "\n";
}

/********************************FUNCTIONS*********************************/

/**
 * Get Tab Nil
 * return an associate array with the different Nil Code
 * @param $handler
 * @param $nb
 * @return bool
 */
function getTabNil($handler, $nb){
    if ($handler){
        for($i = 0; $i < $nb; $i++){
            $bufferNil = fgets($handler);
            $tmp = explode(" ", $bufferNil);
//            echo $tmp[1];
//            var_dump(trim($tmp[1]));
            $code = explode(";", $tmp[0]);
//            var_dump($code);
            $res[$code[0]] = trim($tmp[1]);
            unset($tmp);
        }
        if (!$res){
            echo "\n l'extraction des Code Nil n'a pas fonctionné.";
            return false;
        }else{
            return $res;
        }
    }
    else{
        echo "\n l'ouverture du fichier des code Nil n'a pas fonctionné.";
        return false;
    }
}

/**
 * Get Backup File Name
 * return the name of the file for the backup
 * @param $name
 * @param $suffix
 * @return string
 */
function getBackupfileName($name, $suffix){
    $res = $name.'_'.$suffix;
    return $res;
}

/**
 * Get tab File
 * return an array with all the file inside
 * @param $file
 * @return array
 */
function getTabFile($file){
    $res = array();
    $handle = fopen($file, "r");
    if ($handle){
        while (!feof($handle)){
            $buffer = fgets($handle);
            $res[] = $buffer;
        }
        fclose($handle);
    }
    if($res){
        return $res;
    }else{
        echo "la récupération des informations du fichier a échoué .\n";
        return;
    }
}

/**
 * Backup file
 * store the file source in a Backup Directory with date time associate to the filename
 * @param $file
 * @param $data
 * @return int
 */
function backupFile($file, $data){
    $mybackupfile = fopen($file, 'w');
    if($mybackupfile){
        for($i = 0; $i < (count($data)-1);$i++){
            fputs($mybackupfile, $data[$i]);
        }
        fclose($mybackupfile);
        return 0;
    }else{
        echo "La sauvegarde du fichier a échoué \n";
        return -1;
    }
}
function copyFile($src, $dest){
    if (copy($src, $dest)){
        return 0;
    }else{
        echo "La sauvegarde du fichier a échoué \n";
        retun -1;
    }
}

