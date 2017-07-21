<?php
/**
* User: ydieng
* Script permettant l'archivage sur le FTP Suivi deproduction
*
* Ex: php MyBkpSuiviProd.php --file=C:\Workspace\Scripts\MRoad\bkpSuiviProd_prod.ini
* Ex: php MyBkpSuiviProd.php --file=C:\Workspace\Scripts\MRoad\bkpSuiviProd_local.ini
*/

/**
* Paramétrage des variables du script
*/
//error_reporting(0);// Commentez cette ligne pour debugger le Script et la decommentez en prod
$scriptName = "MyBkpSuiviProd.php";
$verbose = false; // valeur par default FALSE --- ATTENTION ne pas mettre à true passer par les options dans le fichier de configuration
$log = false; // valeur par default FALSE --- ATTENTION ne pas mettre à true passer par les options dans le fichier de configuration
$handle = true ;
$paramLog = array();
$error = "\033[31;1mERROR:\e[0m ";
$warning = "\033[33;1mWARNING:\e[0m ";
$usage = "\e[34;1mUSAGE:\e[0m ";
$info = "\e[34;1mINFO:\e[0m ";
$syntaxErrorMsg = "\e[31;1mSYNTAX ERROR:\e[0m ";
$invalidArgumentMsg = "\e[31;1mINVALID ARGUMENT:\e[0m ";
$invalidOptionMsg = "\e[31;1mINVALID OPTION:\e[0m ";
$launchExMsg = " php ".$scriptName."  --file=C:/Workspace/Scripts/bkpSuiviProd.ini \n";
$confExMsg = "Verifiez le fichier de configuration. \n";
$modeExMsg = "Les modes autorises sont 'ftptoftp' ou 'ftptolocal' ou 'localtoftp' .\n";
$logExMsg = "Les valeurs autorisees sont 'true' ou 'false' .\n";
$logDefaultMsg ="La valeur du parametre log dans le fichier de configuration ne sera pas pris en compte. Valeur par defaut: FALSE\n";
$logOffMsg = "Les Logs par defaut sont desactives.\n";
$verboseOffMsg = "Le mode verbeux par defaut est desactive.\n";
$verboseExMsg = "Les valeurs autorisees sont 'true' ou 'false' .\n";
$ftpListingMsg = "Verifiez le chemin d'acces . Renseignez le chemin a partir du repertoire d'entree et non le chemin depuis la base EX:/ .\n";
$ftpEntryPointMsg = "Le repertoire d'entree sur le serveur est: '";
$ftpConnErrorMsg = "Une erreur est survenu lors de la connexion au serveur FTP: '";
$ftpSignInErrorMsg = "Une erreur est survenu lors de l'identification au serveur FTP: '";
$DirUsageMsg = "Verifiez le chemin du repertoire, verifiez que le chemin ne se termine pas par un '/'\n";
$existingBkpDirMsg = "Le repertoire de sauvegarde existe deja.\n";
$noExistingDirBkp = "Le repertoire de sauvegarde n'existe pas sur le serveur .Aucun fichier ne sera archive. \n";
$startParamOptMsg = "Debut de recuperation des parametres et options .\n";
$endParamOptMsg = "Fin de recuperation des parametres et options .\n";
$startParamConfMsg = "Debut de recuperation des parametres de configuration .\n";
$endParamConfMsg = "Fin de recuperation des parametres de configuration .\n";
$paramConfUsage = "parametre=valeur\n";
$hostSrcErrorMsg = "Le parametre [hostSrc] et sa valeur sont obligatoires dans le fichier de configuration.\n";
$loginSrcErrorMsg = "Le parametre [loginSrc] et sa valeur sont obligatoires dans le fichier de configuration.\n";
$mdpSrcErrorMsg = "Le parametre [mdpSrc] et sa valeur sont obligatoires dans le fichier de configuration.\n";
$dirBkpSrcErrorMsg = "Le parametre [dirBkpSrc] et sa valeur sont obligatoires dans le fichier de configuration.\n";
$dirSrcErrorMsg = "Le parametre [dirSrc] et sa valeur sont obligatoires dans le fichier de configuration.\n";
$bddHostErrorMsg = "Le parametre [bddHost] et sa valeur sont obligatoires dans le fichier de configuration.\n";
$bddPortErrorMsg = "Le parametre [bddPort] et sa valeur sont obligatoires dans le fichier de configuration.\n";
$bddUserErrorMsg = "Le parametre [bddUser] et sa valeur sont obligatoires dans le fichier de configuration.\n";
$bddPasswdErrorMsg = "Le parametre [bddPasswd] et sa valeur sont obligatoires dans le fichier de configuration.\n";
$bddNameErrorMsg = "Le parametre [bddName] et sa valeur sont obligatoires dans le fichier de configuration.\n";
$dateFormat = 'ymd_Hi';
$currentDate = date($dateFormat);
$logFileName= "Log_MyBkpSuiviProd_".$currentDate.".log";


/** *************************************************************Debut du Script*********************************************************************** */
if ($argc < 2){
    $msg = "L'option '--file' est obligatoire.\n";
    echo $syntaxErrorMsg.$msg;
    echo $usage.$launchExMsg;
    return;
}

/**
 * Recuperation des options et parametres
 */
echo $info.$startParamOptMsg;
$paramTab = myGetOpt($argc,$argv);
echo $info.$endParamOptMsg;



/**
 * Recuperation des parametres de configuration
 */
echo $info.$startParamConfMsg;
if(array_key_exists("file", $paramTab)){
    $paramConfig = getTabConfig($paramTab["file"],$error);
    if ($paramConfig == false){
        echo $info.$endParamConfMsg;
        return;
    }
}else{
    $msg = "L'option '--file' est obligatoire.\n";
    echo $syntaxErrorMsg.$msg;
    echo $usage.$launchExMsg;
    echo $info.$endParamConfMsg;
    return;
}
echo $info.$endParamConfMsg;

/**
 * Gestion du mode verbeux
*/
if(array_key_exists("verbose", $paramConfig)){
    if (isset($paramConfig['verbose'])){
        if ($paramConfig['verbose'] == "true"){
            $verbose = true;
            $msg = "Le mode verbeux a ete active.\n";
            echo $info.$msg;
        }elseif ($paramConfig['verbose'] == "false"){
            $verbose = false;
            $msg = "Le mode verbeux a ete desactive.\n";
            echo $warning.$msg;
        }else{
            $msg = "La valeur du parametre verbose n'est pas reconnu.\n";
            echo $invalidArgumentMsg.$msg;
            echo $warning.$verboseOffMsg;
            echo $usage.$verboseExMsg;
        }
    }else{
        $msg = "La valeur du parametre verbose n'est pas valide.\n";
        echo $invalidOptionMsg.$msg;
        echo $warning.$verboseOffMsg;
        echo $usage.$confExMsg;
    }
}else{
    echo $warning.$verboseOffMsg;
    echo $usage.$confExMsg;

}


/**
 * Gestion des logs
 */
if(array_key_exists("log", $paramConfig)){
    if (isset($paramConfig['log'])){
        if ($paramConfig['log'] == "true"){
            if (array_key_exists("logPath", $paramConfig)){
                $logPath = $paramConfig["logPath"];
                if (is_dir($logPath)){
                    $handle = fopen($logPath."/".$logFileName,"a");
                    if($handle){
                        $log = true;
                        myLog($handle,"START");
                        $paramLog['handle'] = $handle;
                        $msg = "les logs ont ete actives et seront stockes dans le fichier suivant: ".$logFileName."\n";
                        echo $info.$msg;
                    }else{
                        $msg = "l'ouverture et/ou la creation du fichier de log a echoue.\n";
                        echo $error.$msg;
                        echo $warning.$logOffMsg;
                        echo $usage."Verifiez les droits sur le repertoire .\n";
                        return;
                    }
                }else{
                    $msg = "Le chemin specifie dans logPath n'est pas valide ou introuvable.\n";
                    echo $error.$msg;
                    echo $warning.$logOffMsg;
                    echo $usage."Verifiez que le repertoire existe ou Renseignez un chemin valide.\n";
                    return;
                }
            }else{
                $msg = "la valeur du parametre logPath n'est pas valide.\n";
                echo $invalidOptionMsg.$msg;
                echo $warning.$logOffMsg;
                echo $usage.$confExMsg;
            }
        }elseif ($paramConfig['log'] == "false"){
            $log = false;
            echo $warning.$logOffMsg;
        }else{
            $msg = "La valeur du parametre log n'est pas valide.\n";
            echo $invalidArgumentMsg.$msg;
            echo $warning.$logOffMsg;
            echo $usage.$logExMsg;
        }
    }else{
        $msg = "la valeur du parametre log n'est pas valide.\n";
        echo $invalidOptionMsg.$msg;
        echo $warning.$logOffMsg;
        echo $usage.$confExMsg;
    }
}else{
    echo $warning.$logOffMsg;
    echo $usage.$confExMsg;
}

$paramLog['log'] = $log;
$paramLog['msg'] = "Commande execute ==> ";
$cmd = getCommandLine($argv, $argc, $paramLog);
$msg = "\e[34;1mCommande execute ==>\e[0m ";
echo $msg.$cmd."\n";


/**
 * Gestion de l'archivage
 */

//Recuperation des parametres de connexion BDD
if((array_key_exists("bddHost", $paramConfig)) && (strlen($paramConfig["bddHost"]) > 0)){
    $bddHost = $paramConfig["bddHost"];
}else{
    echo $error.$bddHostErrorMsg;
    echo $usage.$paramConfUsage;
    ($log == true) ? myLog($handle,"ERROR",$bddHostErrorMsg) : "";
    return;
}
if((array_key_exists("bddPort", $paramConfig)) && (strlen($paramConfig["bddPort"]) > 0)){
    $bddPort = $paramConfig["bddPort"];
}else{
    echo $error.$bddPortErrorMsg;
    echo $usage.$paramConfUsage;
    ($log == true) ? myLog($handle,"ERROR",$bddPortErrorMsg) : "";
    return;
}
if((array_key_exists("bddUser", $paramConfig)) && (strlen($paramConfig["bddUser"]) > 0)){
    $bddUser = $paramConfig["bddUser"];
}else{
    echo $error.$bddUserErrorMsg;
    echo $usage.$paramConfUsage;
    ($log == true) ? myLog($handle,"ERROR",$bddUserErrorMsg) : "";
    return;
}
if((array_key_exists("bddPasswd", $paramConfig)) && (strlen($paramConfig["bddPasswd"]) > 0)){
    $bddPasswd = $paramConfig["bddPasswd"];
}else{
    echo $error.$bddPasswdErrorMsg;
    echo $usage.$paramConfUsage;
    ($log == true) ? myLog($handle,"ERROR",$bddPasswdErrorMsg) : "";
    return;
}
if((array_key_exists("bddName", $paramConfig)) && (strlen($paramConfig["bddName"]) > 0)){
    $bddName = $paramConfig["bddName"];
}else{
    echo $error.$bddNameErrorMsg;
    echo $usage.$paramConfUsage;
    ($log == true) ? myLog($handle,"ERROR",$bddNameErrorMsg) : "";
    return;
}

// Connexion a la BDD
$msg = "Connexion a la BDD  ...........................\n";
if ($verbose == true){
    echo $msg;
}
($log == true) ? myLog($handle,"INFO: ",$msg) : "";
$db = mysqli_connect($bddHost,$bddUser,$bddPasswd,$bddName,$bddPort);
if(!$db){
    $msg = 'Une erreur est survenu lors de la connexion ('. mysqli_connect_errno() . ') ' . mysqli_connect_error();
    ($log == true) ? myLog($handle,"ERROR",$msg) : "";
    die('Une erreur est survenu lors de la connexion ('. mysqli_connect_errno() . ') ' . mysqli_connect_error());
}
$msg = 'Connexion a la BDD reussi:[34;1m' . mysqli_get_host_info($db) . "\e[0m\n";
echo $msg;
$msg = 'Connexion a la BDD reussi:' . mysqli_get_host_info($db) . "\n";
($log == true) ? myLog($handle,"INFO: ",$msg) : "";

//Recuperation de la liste des fichiers a archiver
$msg = "Recuperation de la liste des fichiers a archiver......\n";
echo $msg;
($log == true) ? myLog($handle,"INFO: ",$msg) : "";
$sql = "SELECT nom FROM `fic_recap` where code like '%SUIVI_PRODUCTION%' ";
$req = mysqli_query($db,$sql);
if ($req){
    $tabFile = array();
     while($res = mysqli_fetch_assoc($req)){
        $tabFile[] = $res["nom"];
    }
//    $res = mysqli_fetch_all($req,MYSQLI_NUM);
//    foreach ($res as $tab){
//        $tabFile[] = $tab[0];
//    }
}else{
    $msg = "\e[31;1mERREUR:\e[0m une erreur est survenu lors du listing des fichiers .\n";
    $msg .= "Deconnexion de la BDD ...................\n";
    echo $msg;
    $msg .= "Deconnexion de la BDD ...................\n";
    ($log == true) ? myLog($handle,"INFO: ",$msg) : "";
    $msg = "une erreur est survenu lors du listing des fichiers .\n";
    ($log == true) ? myLog($handle,"ERROR",$msg) : "";
    mysqli_close($db);
    return;
}
$msg = "Deconnexion de la BDD ...................\n";
if($verbose == true ){
    echo $msg;
}
($log == true) ? myLog($handle,"INFO: ",$msg) : "";
mysqli_close($db);

//Recuperation des parametres du FTP
if((array_key_exists("hostSrc", $paramConfig)) && (strlen($paramConfig["hostSrc"]) > 0)){
    $ftpToLocalHost = $paramConfig["hostSrc"];
}else{
    echo $error.$hostSrcErrorMsg;
    echo $usage.$paramConfUsage;
    ($log == true) ? myLog($handle,"ERROR",$hostSrcErrorMsg) : "";
    return;
}
if((array_key_exists("loginSrc", $paramConfig)) && (strlen($paramConfig["loginSrc"]) > 0)){
    $ftpToLocalLogin = $paramConfig["loginSrc"];
}else{
    echo $error.$loginSrcErrorMsg;
    echo $usage.$paramConfUsage;
    ($log == true) ? myLog($handle,"ERROR",$loginSrcErrorMsg) : "";
    return;
}
if((array_key_exists("mdpSrc", $paramConfig)) && (strlen($paramConfig["mdpSrc"]) > 0)){
    $ftpToLocalMdp = $paramConfig["mdpSrc"];
}else{
    echo $error.$mdpSrcErrorMsg;
    echo $usage.$paramConfUsage;
    ($log == true) ? myLog($handle,"ERROR",$mdpSrcErrorMsg) : "";
    return;
}

//Connexion et Identification sur le FTP
$ftpHandle = connectToFtp($ftpToLocalHost,"",$verbose, $log,$handle, $error,$info);
if($ftpHandle === false){
	echo $error.$ftpConnErrorMsg.$ftpToLocalHost."' \n";
    ($log == true) ? myLog($handle,"ERROR",$ftpConnErrorMsg.$ftpToLocalHost."' \n") : "";
    return;
}
if (signToInFtp($ftpHandle,$ftpToLocalHost,$ftpToLocalLogin,$ftpToLocalMdp,$verbose,$log,$handle,$error) == false){
	echo $error.$ftpSignInErrorMsg.$ftpToLocalHost."' \n";
    ($log == true) ? myLog($handle,"ERROR",$ftpSignInErrorMsg.$ftpToLocalHost."' \n") : "";
    return;
}
$msg = "Connexion reussi sur le FTP:'".$ftpToLocalHost. "' .\n";
echo $info.$msg;
($log == true) ? myLog($handle,"INFO: ",$msg) : "";
//echo $info.$ftpEntryPointMsg.ftp_pwd($ftpHandle). "'\n";

//recuperation des parametre sur les repertoires
if ((array_key_exists("dirSrc", $paramConfig)) && (strlen($paramConfig["dirSrc"]) > 0)){
    $dirSrc = $paramConfig["dirSrc"];
}else{
    echo $error.$dirSrcErrorMsg;
    echo $usage.$paramConfUsage;
    ($log == true) ? myLog($handle,"ERROR",$dirSrcErrorMsg) : "";
    return;
}
if ((array_key_exists("dirBkpSrc", $paramConfig)) && (strlen($paramConfig["dirBkpSrc"]) > 0)){
    $dirBkpSrc = $paramConfig["dirBkpSrc"];
}else{
    echo $error.$dirBkpSrcErrorMsg;
    echo $usage.$paramConfUsage;
    ($log == true) ? myLog($handle,"ERROR",$dirBkpSrcErrorMsg) : "";
    return;
}

//Listing des fichiers sur le repertoire distant -----source
$msg = "Listing des fichiers a traiter sur le ftp: ".$ftpToLocalHost."\n";
if ($verbose == true){
    echo $info.$msg;
}
($log == true) ? myLog($handle,"INFO: ",$msg) : "";
$fileList = ftp_nlist($ftpHandle,$dirSrc);
//var_dump(ftp_pwd($ftpHandle));
if ($fileList != false && count($fileList) > 0){
    $msgBkp = "Debut d'archivage des fichiers sur le FTP: ".$ftpToLocalHost." sur l'emplacement suivant: ".$dirBkpSrc."\n";
    if ($verbose == true){
        echo $info.$msgBkp;
    }
    ($log == true) ? myLog($handle,"INFO: ",$msgBkp) : "";
    
    //Verification et creation du dossier d'archivage sur le serveur source
    if(isFtpDirExist($ftpHandle,$dirBkpSrc) == false){
        echo $warning.$noExistingDirBkp;
        ($log == true) ? myLog($handle,"WARNING: ",$noExistingDirBkp) : "";
    }else{
        echo $info.$existingBkpDirMsg;
        ($log == true) ? myLog($handle, "INFO: ", $existingBkpDirMsg) : "";
        $fileBackuped = 0;
        
//        var_dump($tabFile);
        foreach($fileList as $fileFullPath){
            $file = basename($fileFullPath);
//            var_dump($file);
            if (in_array($file,$tabFile, true)){
                $old = $fileFullPath;
                $new = $dirBkpSrc."/".$file;
//                var_dump($old);
//                var_dump($new);
                $ftpRename = ftp_rename($ftpHandle,$old,$new );
                if ($ftpRename == true){
                    $fileBackuped++;
                    $msg = "\e[32;1mle fichier '".$file."' a ete archive sur le FTP source.\e[0m\n";
                    echo $msg;
                    $msg = "le fichier '".$file."' a ete archive sur le FTP source.\n";
                    ($log == true) ? myLog($handle,"INFO: ",$msg) : "";
                }else{
                    $msg = "Une erreur est survenu lors de l'archivage du fichier '".$file."' .\n";
                    echo $warning.$msg;
                    ($log == true) ? myLog($handle,"WARNING: ",$msg) : "";
                }
            }
            unset($file,$fileFullPath);
        }
        $msg = "Fin de l'archivage des fichiers sur le FTP.\n";
        $resumeBackup = $fileBackuped." fichier(s) ont ete archive(s) a l'emplacement suivant: ".$dirBkpSrc."\n";
        echo $info.$msg;
        echo $info.$resumeBackup;
        ($log == true) ? myLog($handle,"INFO: ",$msg) : "";
        ($log == true) ? myLog($handle,"INFO: ",$resumeBackup) : "";
    }
}else{
    $msg = "un probleme est survenu lors du listing des fichiers sur le FTP.\n";
    echo $warning.$msg;
    echo $usage.$ftpListingMsg;
    ($log == true) ? myLog($handle,"WARNING: ",$msg) : "";
}

// Fermeture de la connexion ftp et fin du script
closeFtp($ftpHandle,$ftpToLocalHost,"",$verbose,$log,$handle);
echo "Fin des traitements.\n";
($log == true) ? myLog($handle,"END") : "";
return;



/**
 * *********** Fonctions *********************
 */

/**
 * MyLog
 * Logs les msg dans un fichier
 * 
 * @param $handle
 * @param string $type
 * @param string $msg
 */
function myLog($handle, $type, $msg=""){
    $date = date("D d M Y H:i");
    if($type == "START"){
        fwrite($handle,$date. " -- Création du Fichier\n");
    }elseif($type == "END"){
        fwrite($handle,$date. " -- Fin des Traitements\n");
        fclose($handle);
    }elseif($type == "ERROR"){
        fwrite($handle,$date. " -- ERROR: ".$msg);
        fwrite($handle,$date. " -- Fin des Traitements\n");
        fclose($handle);
    }else{
        fwrite($handle,$date." -- ".$type.$msg);
    }
    return;
}

/**
 * MyGetOpt
 * Recupere tous les parametres avec leurs options puis
 * les stockent dans un tableau associatif
 * 
 * @param $nbparam
 * @param $tabparam
 * @return array
 */
function myGetOpt($nbparam, $tabparam){
    $res = array();
    for ($i = 1;$i < $nbparam;$i++){
        $tmp = explode("=", $tabparam[$i]);
        (isset($tmp[1])) ? $res[substr($tmp[0],2)] = $tmp[1] :  $res[$tmp[0]] = "";
    }
    return $res;
}


/**
 * GetCommandLine
 * renvoie la ligne de commande qui a été executé
 * 
 * @param $tabparam
 * @param $nbparam
 * @param $paramLog
 * @return string
 */
function getCommandLine($tabparam, $nbparam, $paramLog){
    $res = "";
    for ($i = 0;$i < $nbparam;$i++){
        $res .= $tabparam[$i] . " ";
    }
    ($paramLog['log'] == true) ? myLog($paramLog['handle'],"INFO: ",$paramLog['msg'].$res."\n") : "";
    return $res;
}

/**
 * IsFtpDirExist
 * Verifie l'existence d'un repertoire sur un ftp
 * 
 * @param $handle
 * @param $rep
 * @return bool
 */
function isFtpDirExist($handle, $rep){
    $res = ftp_nlist($handle, $rep);
    if ($res === false){
        return false;
    }else{
        return true;
    }
}

/**
 * SignToInFtp
 * cette fonction permet de s'identifier sur un serveur FTP , elle renvoie
 * true: si la connexion est active
 * false: dans le cas echéant
 *
 * @param $ftpHandle
 * @param $host
 * @param $login
 * @param $mdp
 * @param $verbose
 * @param $log
 * @param $logHandle
 * @param $error
 * @return bool
 */
function signToInFtp($ftpHandle, $host, $login, $mdp, $verbose, $log, $logHandle, $error){
    $res = ftp_login($ftpHandle, $login, $mdp);
    if ($res){
        ftp_pasv($ftpHandle, true);
        $msg = "La connexion FTP sur l'hote '\e[32;1m".$host."\e[0m' est active.\n";
        if ($verbose == true){
            echo $msg;
        }
        $msg = "La connexion FTP sur l'hote '".$host."' est active.\n";
        ($log == true) ? myLog($logHandle,"INFO: ",$msg) : "";
        return true;
    }else{
        $msg = "Le couple Login/MotDePasse est incorrect.\n";
        if ($verbose == true){
            echo $error.$msg;
        }
        ($log == true) ? myLog($logHandle,"ERROR",$msg) : "";
        return false;
    }
}


/**
 * ConnectToFtp
 * cette fonction permet de se connecter sur un serveur FTP, elle renvoie
 * la ressource: si la connexion est ouverte
 * false: dans le cas échéant
 *
 * @param $host
 * @param $side
 * @param $verbose
 * @param $log
 * @param $logHandle
 * @param $error
 * @param $info
 * @return bool|resource
 */
function connectToFtp($host, $side,$verbose, $log, $logHandle, $error, $info){
    $ftpConnexionMsg = "Verifiez le parametre hote dans le script.";

    $ftpHandle = ftp_connect($host);
    if($ftpHandle){
        $msg = "Ouverture d'une connexion FTP sur l'hote ".$side." '".$host."' .\n";
        if ($verbose == true){
            echo $msg;
        }
        ($log == true) ? myLog($logHandle,"INFO: ",$msg) : "";
        return $ftpHandle;
    }else{
        $msg = "L'identification sur l'hote ".$side." '".$host."' a echoue .\n";
        if ($verbose == true){
            echo $error.$msg;
            echo $info.$ftpConnexionMsg;
        }
        ($log == true) ? myLog($logHandle,"ERROR",$msg) : "";
        return false;
    }
}

/**
 * CloseFtp
 * cette fonction permet de fermer une connexion ftp active
 *
 * @param $handle
 * @param $host
 * @param $side
 * @param $verbose
 * @param $log
 * @param $logHandle
 */
function closeFtp($handle, $host, $side, $verbose, $log, $logHandle){
    ftp_close($handle);
    $msg = "Fermeture de la connexion FTP sur l'hote ".$side." '".$host."'\n";
    if($verbose == true){
        echo "\e[34;1mINFO:\e[0m ".$msg;
    }
    ($log == true) ? myLog($logHandle,"INFO: ",$msg) : "";
    return;
}

/**
 * GetTabConfig
 * cette fonction renvoie un tableau contenant les parametres de configuration
 * @param $path
 * @param $error
 * @return array|bool
 */
function getTabConfig($path, $error){
    $res = array();
    $handler = fopen($path,'r');
    if($handler){
        while (!feof($handler)){
            $buffer = fgets($handler);
            $tmp = explode("=",$buffer);
            if (strlen(trim($tmp[0])) > 0 && isset($tmp[1])){
                if(trim($tmp[0]) == "log"){
                    $res[trim($tmp[0])] = trim($tmp[1]);
                }elseif(trim($tmp[0]) == "verbose"){
                    $res[trim($tmp[0])] = trim($tmp[1]);
                }else{
                    $res[trim($tmp[0])] = trim($tmp[1]);
                }
            }
            unset($tmp);
        }
        fclose($handler);
        if($res){
            return $res;
        }else{
            echo $error."La recuperation des parametres de configuration a echoue .\n";
            return;
        }

    }else{
        echo $error."Une erreur est survenu lors de l'ouverture du fichier de configuration.\n";
        return false;
    }
}
