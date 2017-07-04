<?php
/**
 * Script permettant de loader le fichier des transco en BDD
 * Created by PhpStorm.
 * User: ydieng
 * Date: 27/06/2017
 * Time: 10:38
 */


/**
* Paramétrages des variables
*/
$scriptName = "MyLoadDataInFile.php";
$inputFilename= 'C:/Workspace/Scripts/PAL_TRANSCO_CLIENT_CSV.txt';
$bddHost = '10.231.52.112';
$bddPort = '3306';
$bddUser = 'jadeadmin';
$bddPasswd = 'jade123';
$bddName = 'jade';
$tableName = "transco_abo_dcs_gli_temp";

// Connexion a la BDD
echo "Connexion a la BDD  ...........................\n";
$db = mysqli_connect($bddHost,$bddUser,$bddPasswd,$bddName,$bddPort);
if(!$db){
    die('Une erreur est survenu lors de la connexion ('. mysqli_connect_errno() . ') ' . mysqli_connect_error());
}
echo 'Connexion a la BDD reussi:[34;1m' . mysqli_get_host_info($db) . "\e[0m\n";

// Creation de la table -------- TJ_TRANSCO_ABO
echo "Creation de la table '".$tableName."' ...........\n";
$sql = 'CREATE TABLE IF NOT EXISTS `'.$bddName.'`.`'.$tableName.'` (`num_dcs` INT(20) NOT NULL , `num_gli` VARCHAR(20) NOT NULL) ENGINE=InnoDB ; ';
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
$sql .= "(num_dcs, num_gli);";
$req = mysqli_query($db,$sql);
if ($req){
    echo "Le fichier  `".$inputFilename."`  a ete charge avec succes .\n";
}else{
    echo "\e[31;1mERREUR:\e[0m une erreur est survenu lors du chargement du fichier `".$inputFilename."` .\n";
    echo "Deconnexion de la BDD ...................\n";
    mysqli_close($db);
    return;
}

echo "Deconnexion de la BDD ...................\n";
mysqli_close($db);