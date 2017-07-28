<?php
/**
 * Script permettant de loader le fichier des transco en BDD
 * Created by PhpStorm.
 * User: ydieng
 * Date: 27/06/2017
 * Time: 10:38
 * 
 * EX: php MyLoadDataInFile.php
 */


/**
* Paramétrages des variables
*/
$scriptName = "MyLoadDataInFile.php";
//$inputFilename= 'C:/Workspace/Scripts/Transco-JADE-MRoad/PAL_TRANSCO_CLIENT_CSV_PROD.txt';
$inputFilename= 'C:/Workspace/Scripts/MRoad/Routes_20170720_mod.csv';
$bddHost = 'py-ch-prdmrapp.adonis.mediapole.info';
//$bddHost = '10.231.52.156';
$bddPort = '3306';
$bddUser = 'mroad';
//$bddUser = 'dev-mroad';
$bddPasswd = 'Mroad123';
//$bddPasswd = 'dev-mroad';
$bddName = 'mroad';
$tableName = "depot_route_suivi_prod";

// Connexion a la BDD
echo "Connexion a la BDD  ...........................\n";
$db = mysqli_connect($bddHost,$bddUser,$bddPasswd,$bddName,$bddPort);
if(!$db){
    die('Une erreur est survenu lors de la connexion ('. mysqli_connect_errno() . ') ' . mysqli_connect_error());
}
echo 'Connexion a la BDD reussi:[34;1m' . mysqli_get_host_info($db) . "\e[0m\n";

// Creation de la table -------- 
echo "Creation de la table '".$tableName."' ...........\n";
//$sql = 'CREATE TABLE IF NOT EXISTS `'.$bddName.'`.`'.$tableName.'` (`num_dcs` INT(10) NOT NULL , `num_gli` VARCHAR(11) NOT NULL) ENGINE=InnoDB CHARACTER SET = utf8 , COLLATE = utf8_unicode_ci  ; ';
$sql = 'CREATE TABLE IF NOT EXISTS `'.$bddName.'`.`'.$tableName.'` (`id` INT NOT NULL AUTO_INCREMENT ,`code_route` VARCHAR(10) NOT NULL , `libelle_route` VARCHAR(40) ,`code_centre` VARCHAR(5) NOT NULL,`libelle_centre` VARCHAR(40), PRIMARY KEY(`id`), KEY `IX_code_route` (`code_route`), KEY `IX_code_centre` (`code_centre`)) ENGINE=InnoDB DEFAULT CHARACTER SET = utf8 , COLLATE = utf8_unicode_ci  ; ';
//var_dump($sql);
$req = mysqli_query($db,$sql);
if ($req){
    echo 'La table `'.$tableName."` a ete cree avec succes .\n";
}else{
    echo "\e[31;1mERREUR:\e[0m une erreur est survenu lors de la creation de la table `".$tableName."` .\n";
    echo "Deconnexion de la BDD ...................\n";
    mysqli_close($db);
    return;
}
unset($sql,$req);

//Chargement du fichier en BDD
echo "Chargement du fichier '".$inputFilename."' en BDD  ......\n";

$sql = "LOAD DATA LOCAL INFILE '" .$inputFilename."' ";
$sql .= "INTO TABLE ".$tableName." ";
$sql .= "FIELDS TERMINATED BY ';' ENCLOSED BY '' ESCAPED BY '' ";
$sql .= "LINES STARTING BY '' TERMINATED BY '\\r\\n' ";
$sql .= "IGNORE 1 LINES ";
//$sql .= "(num_dcs, num_gli);";
$sql .= "(code_route, libelle_route, code_centre, libelle_centre);";
$req = mysqli_query($db,$sql);
if ($req){
    echo "Le fichier  `".$inputFilename."`  a ete charge avec succes .\n";
//    $column = 'num_dcs';
//    $sqlAlter = 'ALTER TABLE `'.$bddName.'`.`'.$tableName.'` CHANGE COLUMN `'.$column.'` `'.$column.'` VARCHAR(10) NOT NULL';
//    $reqAlter = mysqli_query($db,$sqlAlter);
//    if ($reqAlter){
//        echo 'Le champ `'.$column."` a ete modifiee avec succes .\n";
//    }else{
//        echo "\e[31;1mERREUR:\e[0m une erreur est survenu lors de la modification du champ `".$column."` .\n";
//        echo "Deconnexion de la BDD ...................\n";
//        mysqli_close($db);
//        return;
//    }
}else{
    echo "\e[31;1mERREUR:\e[0m une erreur est survenu lors du chargement du fichier `".$inputFilename."` .\n";
    echo "Deconnexion de la BDD ...................\n";
    mysqli_close($db);
    return;
}

;