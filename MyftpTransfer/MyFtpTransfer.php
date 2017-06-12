<?php
/**
 * User: ydieng
 * Script permettant la recupération et le transfert de fichiers sur un FTP/Local
 *
 * Ex: php MyFtpTransfer.php --mode=ftptoftp --file=/home/dev-mroad/conf/ftptoftp.ini
 *
 * Ex: php MyFtpTransfer.php --mode=ftptolocal --file=/home/dev-mroad/conf/ftptolocal.ini
 *
 * Ex: php MyFtpTransfer.php --mode=localtoftp --file=/home/dev-mroad/conf/localtoftp.ini
 */

/**
 * Paramétrage des variables du script
 */
//error_reporting(0);// Commentez cette ligne pour debugger le Script et la decommentez en prod
$scriptName = "MyFtpTransfer.php";
$verbose = false; // valeur par default FALSE --- ATTENTION ne pas mettre à true passer par les options en parametre
$log = false; // valeur par default FALSE --- ATTENTION ne pas mettre à true passer par les options en parametre
$handle = true ;
$paramLog = array();
$error = "\033[31;1mERROR:\e[0m ";
$warning = "\033[33;1mWARNING:\e[0m ";
$usage = "\e[34;1mUSAGE:\e[0m ";
$info = "\e[34;1mINFO:\e[0m ";
$syntaxErrorMsg = "\e[31;1mSYNTAX ERROR:\e[0m ";
$invalidArgumentMsg = "\e[31;1mINVALID ARGUMENT:\e[0m ";
$invalidOptionMsg = "\e[31;1mINVALID OPTION:\e[0m ";
$launchExMsg = " php ".$scriptName." --mode=[ftptoftp|ftptolocal|localtoftp] --file=C:/Workspace/Scripts/[ftptoftp|ftptolocal|localtoftp].ini \n";
$confExMsg = "Verifiez le fichier de configuration. \n";
$modeExMsg = "Les modes autorises sont 'ftptoftp' ou 'ftptolocal' ou 'localtoftp' .\n";
$logExMsg = "Les valeurs autorisees sont 'true' ou 'false' .\n";
$logDefaultMsg ="La valeur du parametre log dans le fichier de configuration ne sera pas pris en compte. Valeur par defaut: FALSE\n";
$logOffMsg = "Les Logs par defaut sont desactives.\n";
$verboseOffMsg = "Le mode verbeux par defaut est desactive.\n";
$verboseExMsg = "Les valeurs autorisees sont 'true' ou 'false' .\n";
$ftpListingMsg = "Verifiez le chemin d'acces . Renseignez le chemin a partir du repertoire d'entree et non le chemin depuis la base EX:/home .\n";
$ftpEntryPointMsg = "Le repertoire d'entree sur le serveur est: '";
$DirUsageMsg = "Verifiez le chemin du repertoire, verifiez que le chemin ne se termine pas par un '/'\n";
$existingBkpDirMsg = "Le repertoire de sauvegarde existe deja.\n";
$startParamOptMsg = "Debut de recuperation des parametres et options .\n";
$endParamOptMsg = "Fin de recuperation des parametres et options .\n";
$startParamConfMsg = "Debut de recuperation des parametres de configuration .\n";
$endParamConfMsg = "Fin de recuperation des parametres de configuration .\n";
$paramConfUsage = "parametre=valeur\n";
$hostSrcErrorMsg = "Le parametre [hostSrc] et sa valeur sont obligatoires dans le fichier de configuration.\n";
$hostDestErrorMsg = "Le parametre [hostDest] et sa valeur sont obligatoires dans le fichier de configuration.\n";
$loginSrcErrorMsg = "Le parametre [loginSrc] et sa valeur sont obligatoires dans le fichier de configuration.\n";
$loginDestErrorMsg = "Le parametre [loginDest] et sa valeur sont obligatoires dans le fichier de configuration.\n";
$mdpSrcErrorMsg = "Le parametre [mdpSrc] et sa valeur sont obligatoires dans le fichier de configuration.\n";
$mdpDestErrorMsg = "Le parametre [mdpDest] et sa valeur sont obligatoires dans le fichier de configuration.\n";
$dirSrcErrorMsg = "Le parametre [dirSrc] et sa valeur sont obligatoires dans le fichier de configuration.\n";
$dirBkpSrcErrorMsg = "Le parametre [dirBkpSrc] et sa valeur sont obligatoires dans le fichier de configuration.\n";
$dirLocalErrorMsg = "Le parametre [dirLocal] et sa valeur sont obligatoires dans le fichier de configuration.\n";
$dirDestErrorMsg = "Le parametre [dirDest] et sa valeur sont obligatoires dans le fichier de configuration.\n";
$dirTmpErrorMsg = "Le parametre [dirTmp] et sa valeur sont obligatoires dans le fichier de configuration.\n";
$dirBkpLocalErrorMsg = "Le parametre [dirBkpLocal] et sa valeur sont obligatoires dans le fichier de configuration.\n";
$regexErrorMsg = "Le parametre [regex] et sa valeur sont obligatoires dans le fichier de configuration.\n";
$dateFormat = 'ymdHi';
$currentDate = date($dateFormat);
$logFileName= "Log_".$currentDate.".txt";
$shortOpts = "";
$longOpts = array(
                    "mode:",
                    "file:",
    );

/** *************************************************************Debut du Script*********************************************************************** */
if ($argc < 3){
    $msg = "Les options '--mode' et '--file' sont obligatoires.\n";
    echo $syntaxErrorMsg.$msg;
    echo $usage.$launchExMsg;
    return;
}

//Recuperation des options et parametres
echo $info.$startParamOptMsg;
$paramTab = myGetOpt($argc,$argv);
//$paramTab = myGetOpt($shortOpts,$longOpts);
echo $info.$endParamOptMsg;

/**
 * Gestion des parametre de configuration
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
 * Gestion des modes de transfert
 *      ftptoftp
 *      ftptolocal
 *      localtoftp
 */
if(array_key_exists("mode", $paramTab) && isset($paramTab["mode"])){
    $mode = strtolower($paramTab["mode"]);
    switch ($mode){
        case "ftptoftp":
            $msg = "\t\e[34;1mMODE:\e[0m  ".$mode."\n";
            echo $msg;
            $msg = "MODE:  ".$mode."\n";
            ($log == true) ? myLog($handle,"INFO: ",$msg) : "";

            //recuperation des parametres de connection de la source
            if((array_key_exists("hostSrc", $paramConfig)) && (strlen($paramConfig["hostSrc"]) > 0)){
                $ftpToFtpHostSrc = $paramConfig["hostSrc"];
            }else{
                echo $error.$hostSrcErrorMsg;
                echo $usage.$paramConfUsage;
                ($log == true) ? myLog($handle,"ERROR",$hostSrcErrorMsg) : "";
                break;
            }
            if((array_key_exists("loginSrc", $paramConfig)) && (strlen($paramConfig["loginSrc"]) > 0)){
                $ftpToFtpLoginSrc = $paramConfig["loginSrc"];
            }else{
                echo $error.$loginSrcErrorMsg;
                echo $usage.$paramConfUsage;
                ($log == true) ? myLog($handle,"ERROR",$loginSrcErrorMsg) : "";
                break;
            }
            if((array_key_exists("mdpSrc", $paramConfig)) && (strlen($paramConfig["mdpSrc"]) > 0)){
                $ftpToFtpMdpSrc = $paramConfig["mdpSrc"];
            }else{
                echo $error.$mdpSrcErrorMsg;
                echo $usage.$paramConfUsage;
                ($log == true) ? myLog($handle,"ERROR",$mdpSrcErrorMsg) : "";
                break;
            }

            //Connexions et Identifications aux FTP source
            $ftpHandleSrc = connectToFtp($ftpToFtpHostSrc,"source",$verbose, $log,$handle, $error,$info);
            if($ftpHandleSrc === false){
                break;
            }
            if (signToInFtp($ftpHandleSrc,$ftpToFtpHostSrc,$ftpToFtpLoginSrc,$ftpToFtpMdpSrc,$verbose,$log,$handle,$error) == false){
                break;
            }
            echo $info.$ftpEntryPointMsg.ftp_pwd($ftpHandleSrc). "'\n";



            //recuperation des parametres de connection de la destination
            if((array_key_exists("hostDest", $paramConfig)) && (strlen($paramConfig["hostDest"]) > 0)){
                $ftpToFtpHostDest = $paramConfig["hostDest"];
            }else{
                echo $error.$hostDestErrorMsg;
                echo $usage.$paramConfUsage;
                ($log == true) ? myLog($handle,"ERROR",$hostDestErrorMsg) : "";
                break;
            }
            if((array_key_exists("loginDest", $paramConfig)) && (strlen($paramConfig["loginDest"]) > 0)){
                $ftpToFtpLoginDest = $paramConfig["loginDest"];
            }else{
                echo $error.$loginDestErrorMsg;
                echo $usage.$paramConfUsage;
                ($log == true) ? myLog($handle,"ERROR",$loginDestErrorMsg) : "";
                break;
            }
            if((array_key_exists("mdpDest", $paramConfig)) && (strlen($paramConfig["mdpDest"]) > 0)){
                $ftpToFtpMdpDest = $paramConfig["mdpDest"];
            }else{
                echo $error.$mdpDestErrorMsg;
                echo $usage.$paramConfUsage;
                ($log == true) ? myLog($handle,"ERROR",$mdpDestErrorMsg) : "";
                break;
            }

            //Connexions et Identifications aux FTP destination
            $ftpHandleDest = connectToFtp($ftpToFtpHostDest,"de destination",$verbose, $log,$handle, $error,$info);
            if($ftpHandleDest === false){
                break;
            }
            if (signToInFtp($ftpHandleDest,$ftpToFtpHostDest,$ftpToFtpLoginDest,$ftpToFtpMdpDest,$verbose,$log,$handle,$error) == false){
                break;
            }
            echo $info.$ftpEntryPointMsg.ftp_pwd($ftpHandleDest). "'\n";



//            echo"je suis en phase de test avec un break\n";
//            break;

            //recuperation des parametre sur les repertoires
            if ((array_key_exists("dirSrc", $paramConfig)) && (strlen($paramConfig["dirSrc"]) > 0)){
                $ftpToFtpDirSrc = $paramConfig["dirSrc"];
            }else{
                echo $error.$dirSrcErrorMsg;
                echo $usage.$paramConfUsage;
                ($log == true) ? myLog($handle,"ERROR",$dirSrcErrorMsg) : "";
                break;
            }
            if ((array_key_exists("dirBkpSrc", $paramConfig)) && (strlen($paramConfig["dirBkpSrc"]) > 0)){
                $ftpToFtpDirBkpSrc = $paramConfig["dirBkpSrc"];
            }else{
                echo $error.$dirBkpSrcErrorMsg;
                echo $usage.$paramConfUsage;
                ($log == true) ? myLog($handle,"ERROR",$dirBkpSrcErrorMsg) : "";
                break;
            }
            if ((array_key_exists("dirDest", $paramConfig)) && (strlen($paramConfig["dirDest"]) > 0)){
                $ftpToFtpDirDest =  $paramConfig["dirDest"];
            }else{
                echo $error.$dirDestErrorMsg;
                echo $usage.$paramConfUsage;
                ($log == true) ? myLog($handle,"ERROR",$dirDestErrorMsg) : "";
                break;
            }
            if ((array_key_exists("dirTmp", $paramConfig)) && (strlen($paramConfig["dirTmp"]) > 0)){
                $ftpToFtpDirTmp =  $paramConfig["dirTmp"];
            }else{
                echo $error.$dirTmpErrorMsg;
                echo $usage.$paramConfUsage;
                ($log == true) ? myLog($handle,"ERROR",$dirTmpErrorMsg) : "";
                break;
            }
            if ((array_key_exists("regex", $paramConfig)) && (strlen($paramConfig["regex"]) > 0)){
                $regexDefault =  $paramConfig["regex"];
            }else{
                echo $error.$regexErrorMsg;
                echo $usage.$paramConfUsage;
                ($log == true) ? myLog($handle,"ERROR",$regexErrorMsg) : "";
                break;
            }

            //Listing des fichiers sur le repertoire distant -----source
            $msg = "Listing des fichiers a traiter sur le ftp source: ".$ftpToFtpDirSrc."\n";
            if ($verbose == true){
                echo $msg;
            }
            ($log == true) ? myLog($handle,"INFO: ",$msg) : "";
            $fileList = ftp_nlist($ftpHandleSrc,$ftpToFtpDirSrc);
            if ($fileList != false && count($fileList) > 0){
                $flagRecup = false;
                $flagBkp = false;
                $msgDownload = "Debut du telechargement des fichiers en local sur l'emplacement suivant: ".$ftpToFtpDirTmp."\n";
                $msgBackup = "creation du dossier d'archivage sur l'emplacement suivant: ".$ftpToFtpDirBkpSrc."\n";
                echo $msgDownload;
                if ($verbose == true){
                    echo $msgBackup;
                }
                ($log == true) ? myLog($handle,"INFO: ",$msgDownload) : "";
                ($log == true) ? myLog($handle,"INFO: ",$msgBackup) : "";

                //Verification et creation du dossier d'archivage sur le serveur source
                if(isFtpDirExist($ftpHandleSrc,$ftpToFtpDirBkpSrc) == false){
                    $bkp = ftp_mkdir($ftpHandleSrc, $ftpToFtpDirBkpSrc);
                    if ($bkp == false){
                        $flagBkp = true;
                        $msg = "Un probleme est survenu lors de la creation du repertoire de Backup.\n";
                        if ($verbose == true){
                            echo $warning.$msg;
                        }
                        ($log == true) ? myLog($handle,"WARNING: ",$msg) : "";
                    }
                }else{
                    if ($verbose == true){
                        echo $info.$existingBkpDirMsg;
                    }
                    ($log == true) ? myLog($handle,"INFO: ",$existingBkpDirMsg) : "";
                }
                $fileTreatedSrc = 0;
                $fileBackuped = 0;

                //Telechargement et archivage
                foreach($fileList as $file){
                    preg_match($regexDefault, basename($file), $matches);
                    if (isset($matches[0])){

                        // ICI on va recuperer les fichiers et les traiter avant de les telecharger
                        $localFile = $ftpToFtpDirTmp."/".$matches[0];
                        $res = ftp_get($ftpHandleSrc,$localFile, $file,FTP_BINARY);
                        if ($res == true){
                            $fileTreatedSrc++;
                            $flagRecup = true;
                            $msg = "\e[32;1mLe transfert du fichier '".$matches[0]."' en local a reussi.\e[0m\n";
                            if ($verbose == true){
                                echo $msg;
                            }
                            $msg = "Le transfert du fichier '".$matches[0]."' en local a reussi.\n";
                            ($log == true) ? myLog($handle,"INFO: ",$msg) : "";

                            //ici on archive le fichier
                            if ($flagBkp == true){
                                $msg = "le fichier '".$matches[0]."' n'a pas pu etre archive.\n";
                                if ($verbose == true){
                                    echo $warning.$msg;
                                }
                                ($log == true) ? myLog($handle,"INFO: ",$msg) : "";
                            }else{
                                $old = $file;
                                $new = $ftpToFtpDirBkpSrc."/".$matches[0];
                                $ftpRename = ftp_rename($ftpHandleSrc,$old,$new );
                                if ($ftpRename == true){
                                    $fileBackuped++;
                                    $msg = "\e[32;1mle fichier '".$matches[0]."' a ete archive sur le FTP source.\e[0m\n";
                                    if ($verbose == true){
                                        echo $msg;
                                    }
                                    $msg = "le fichier '".$matches[0]."' a ete archive sur le FTP source.\n";
                                    ($log == true) ? myLog($handle,"INFO: ",$msg) : "";
                                }else{
                                    $msg = "Une erreur est survenu lors de l'archivage du fichier '".$matches[0]."' .\n";
                                    if ($verbose == true){
                                        echo $warning.$msg;
                                    }
                                    ($log == true) ? myLog($handle,"WARNING: ",$msg) : "";
                                }
                            }
                        }else{
                            $msg = "Le transfert du fichier '".$matches[0]."' en local a echoue.\n";
                            if ($verbose == true){
                                echo $warning.$msg;
                            }
                            ($log == true) ? myLog($handle,"WARNING: ",$msg) : "";
                        }
                    }
                    unset($matches);
                }
                $msg = "Fin du telechargement des fichiers de la source vers le local.\n";
                $resumeTreatSrc = $fileTreatedSrc." fichier(s) ont ete telecharge(s) temporairement a l'emplacement suivant: ".$ftpToFtpDirTmp."\n";
                $resumeBackup = $fileBackuped." fichier(s) ont ete archive(s) a l'emplacement suivant: ".$ftpToFtpDirBkpSrc."\n";
                echo $msg;
                echo $resumeTreatSrc;
                echo $resumeBackup;
                ($log == true) ? myLog($handle,"INFO: ",$msg) : "";
                ($log == true) ? myLog($handle,"INFO: ",$resumeTreatSrc) : "";
                ($log == true) ? myLog($handle,"INFO: ",$resumeBackup) : "";
            }else{
                $flagRecup = false;
                $msg = "un probleme est survenu lors du listing des fichiers sur le ftp source.\n";
                if ($verbose == true){
                    echo $warning.$msg;
                    echo $usage.$ftpListingMsg;
                }
                ($log == true) ? myLog($handle,"WARNING: ",$msg) : "";
            }

            //upload vers le FTP destination
            if ($fileTreatedSrc > 0){
                $fileTreatedDest = 0;
                $fileDeleted = 0;
                $fileListToSend = scandir($ftpToFtpDirTmp);
                unset($fileListToSend[0]);
                unset($fileListToSend[1]);
                $fileListToSend = array_values($fileListToSend);
                foreach($fileListToSend as $fileToSend){
                    $remoteFile = $ftpToFtpDirDest."/".$fileToSend;
                    $localFile = $ftpToFtpDirTmp."/".$fileToSend;
                    $res = ftp_put($ftpHandleDest, $remoteFile, $localFile, FTP_BINARY);
                    if ($res == true){
                        $fileTreatedDest++;
                        $msg = "\e[32;1mLe transfert du fichier '".$fileToSend."' sur le FTP de destination a reussi.\e[0m\n";
                        if ($verbose == true){
                            echo $msg;
                        }
                        $msg = "Le transfert du fichier '".$fileToSend."' sur le FTP de destination a reussi.\n";
                        ($log == true) ? myLog($handle,"INFO: ",$msg) : "";

                        //ici on supprime le fichier qui a ete uploade
                        if(unlink($localFile) == true){
                            $fileDeleted++;
                            $msg = "\e[32;1mle fichier '".$fileToSend."' a ete supprime du repertoire temporaire .\e[0m\n";
                            if ($verbose == true){
                                echo $msg;
                            }
                            $msg = "le fichier '".$fileToSend."' a ete supprime du repertoire temporaire .\n";
                            ($log == true) ? myLog($handle,"INFO: ",$msg) : "";
                        }else{
                            $msg = "Une erreur est survenu lors de la suppression du fichier '".$fileToSend."' dans le repertoire en local.\n";
                            if ($verbose == true){
                                echo $warning.$msg;
                            }
                            ($log == true) ? myLog($handle,"WARNING: ",$msg) : "";
                        }
                    }else{
                        $msg = "Le transfert du fichier '".$fileToSend."' sur le FTP de destination a echoue.\n";
                        if ($verbose == true){
                            echo $warning.$msg;
                            echo $usage.$DirUsageMsg;
                        }
                        ($log == true) ? myLog($handle,"WARNING: ",$msg) : "";
                    }
                }
                $msg = "Fin du telechargement des fichiers de la source vers de destination.\n";
                $resumeTreatDest = $fileTreatedDest." fichier(s) ont ete uploade(s)  a l'emplacement suivant: ".$ftpToFtpDirDest."\n";
                $resumeDelete = $fileDeleted." fichier(s) ont ete supprime(s) de l'emplacement suivant: ".$ftpToFtpDirTmp."\n";
                echo $msg;
                echo $resumeTreatDest;
                echo $resumeDelete;
                ($log == true) ? myLog($handle,"INFO: ",$msg) : "";
                ($log == true) ? myLog($handle,"INFO: ",$resumeTreatDest) : "";
                ($log == true) ? myLog($handle,"INFO: ",$resumeDelete) : "";
            }else{
                $msg = "Aucun Fichier n'a ete envoye sur le FTP de destination.\n";
                if ($verbose == true){
                    echo $warning.$msg;
                }
                ($log == true) ? myLog($handle,"WARNING: ",$msg) : "";
            }

            // On ferme les connexions ftp et termine le script
            closeFtp($ftpHandleSrc,$ftpToFtpHostSrc,"source",$verbose,$log,$handle);
            closeFtp($ftpHandleDest,$ftpToFtpHostDest,"de destination",$verbose,$log,$handle);
            echo "Fin des traitements.\n";
            ($log == true) ? myLog($handle,"END") : "";
            break;

        case "ftptolocal":
            $msg = "\t\e[34;1mMODE:\e[0m  ".$mode."\n";
            echo $msg;
            $msg = "MODE:  ".$mode."\n";
            ($log == true) ? myLog($handle,"INFO: ",$msg) : "";

            //recuperation des parametres de connection de la source
            if((array_key_exists("hostSrc", $paramConfig)) && (strlen($paramConfig["hostSrc"]) > 0)){
                $ftpToLocalHost = $paramConfig["hostSrc"];
            }else{
                echo $error.$hostSrcErrorMsg;
                echo $usage.$paramConfUsage;
                ($log == true) ? myLog($handle,"ERROR",$hostSrcErrorMsg) : "";
                break;
            }
            if((array_key_exists("loginSrc", $paramConfig)) && (strlen($paramConfig["loginSrc"]) > 0)){
                $ftpToLocalLogin = $paramConfig["loginSrc"];
            }else{
                echo $error.$loginSrcErrorMsg;
                echo $usage.$paramConfUsage;
                ($log == true) ? myLog($handle,"ERROR",$loginSrcErrorMsg) : "";
                break;
            }
            if((array_key_exists("mdpSrc", $paramConfig)) && (strlen($paramConfig["mdpSrc"]) > 0)){
                $ftpToLocalMdp = $paramConfig["mdpSrc"];
            }else{
                echo $error.$mdpSrcErrorMsg;
                echo $usage.$paramConfUsage;
                ($log == true) ? myLog($handle,"ERROR",$mdpSrcErrorMsg) : "";
                break;
            }

            //Connexion et Identification sur le FTP
            $ftpHandle = connectToFtp($ftpToLocalHost,"",$verbose, $log,$handle, $error,$info);
            if($ftpHandle === false){
                break;
            }
            if (signToInFtp($ftpHandle,$ftpToLocalHost,$ftpToLocalLogin,$ftpToLocalMdp,$verbose,$log,$handle,$error) == false){
                break;
            }
            echo $info.$ftpEntryPointMsg.ftp_pwd($ftpHandle). "'\n";

            //Recuperation des parametres sur les repertoires
            if ((array_key_exists("dirSrc", $paramConfig)) && (strlen($paramConfig["dirSrc"]) > 0)){
                $ftpToLocalDirSrc = $paramConfig["dirSrc"];
            }else{
                echo $error.$dirSrcErrorMsg;
                echo $usage.$paramConfUsage;
                ($log == true) ? myLog($handle,"ERROR",$dirSrcErrorMsg) : "";
                break;
            }
            if ((array_key_exists("dirBkpSrc", $paramConfig)) && (strlen($paramConfig["dirBkpSrc"]) > 0)){
                $ftpToLocalDirBkpSrc = $paramConfig["dirBkpSrc"];
            }else{
                echo $error.$dirBkpSrcErrorMsg;
                echo $usage.$paramConfUsage;
                ($log == true) ? myLog($handle,"ERROR",$dirBkpSrcErrorMsg) : "";
                break;
            }
            if ((array_key_exists("dirLocal", $paramConfig)) && (strlen($paramConfig["dirLocal"]) > 0)){
                $ftpToLocalDirDest =  $paramConfig["dirLocal"];
            }else{
                echo $error.$dirLocalErrorMsg;
                echo $usage.$paramConfUsage;
                ($log == true) ? myLog($handle,"ERROR",$dirLocalErrorMsg) : "";
                break;
            }
            if ((array_key_exists("regex", $paramConfig)) && (strlen($paramConfig["regex"]) > 0)){
                $regexDefault =  $paramConfig["regex"];
            }else{
                echo $error.$regexErrorMsg;
                echo $usage.$paramConfUsage;
                ($log == true) ? myLog($handle,"ERROR",$regexErrorMsg) : "";
                break;
            }

            //Listing des fichiers
            $msg = "Listing des fichiers a traiter sur l'emplacement suivant: ".$ftpToLocalDirSrc."\n";
            if ($verbose == true){
                echo $msg;
            }
            ($log == true) ? myLog($handle,"INFO: ",$msg) : "";
            $fileList = ftp_nlist($ftpHandle,$ftpToLocalDirSrc);
            if ($fileList != false && count($fileList) > 0){
                $flagBkp = false;
                $msgDownload = "Debut du telechargement et de l'archivage des fichiers sur l'emplacement suivant: ".$ftpToLocalDirDest."\n";
                $msgBackup = "Creation du repertoire d'archivage sur l'emplacement suivant: ".$ftpToLocalDirBkpSrc."\n";
                echo $msgDownload;
                if ($verbose == true){
                    echo $msgBackup;
                }
                ($log == true) ? myLog($handle,"INFO: ",$msgDownload) : "";
                ($log == true) ? myLog($handle,"INFO: ",$msgBackup) : "";

                //verification et creation du dossier d'archivage sur le serveur
                if(isFtpDirExist($ftpHandle,$ftpToLocalDirBkpSrc) == false){
                    $bkp = ftp_mkdir($ftpHandle, $ftpToLocalDirBkpSrc);
                    if ($bkp == false){
                        $flagBkp = true;
                        $msg = "Un Probleme est survenu lors de la creation du repertoire.\n";
                        if ($verbose == true){
                            echo $warning.$msg;
                        }
                        ($log == true) ? myLog($handle,"WARNING: ",$msg) : "";
                    }
                }else{
                    if ($verbose == true){
                        echo $info.$existingBkpDirMsg;
                    }
                    ($log == true) ? myLog($handle,"INFO: ",$existingBkpDirMsg) : "";
                }
                $fileTreated = 0;
                $fileBackuped = 0;
                //Telechargement et archivage
                foreach ($fileList as $file){
                    preg_match($regexDefault,basename($file),$matches);
                    if (isset($matches[0])){
                        //ici on va recuperer les fichiers on les traite avant de les telecharge
                        $localFile = $ftpToLocalDirDest."/".$matches[0];
                        $res = ftp_get($ftpHandle, $localFile, $file, FTP_BINARY);
                        if($res == true){
                            $fileTreated++;
                            $msg = "\e[32;1mLe transfert du fichier '".$matches[0]."' a reussi.\e[0m\n";
                            if ($verbose == true){
                                echo $msg;
                            }
                            $msg = "Le transfert du fichier '".$matches[0]."' a reussi.\n";
                            ($log == true) ? myLog($handle,"INFO: ",$msg) : "";
                            //ici on archive les fichiers
                            if ($flagBkp == true){
                                $msg = "le fichier '".$matches[0]."' n'a pas pu etre archive.\n";
                                if ($verbose == true){
                                    echo $warning.$msg;
                                }
                                ($log == true) ? myLog($handle,"INFO: ",$msg) : "";
                            }else{
                                $old = $file;
                                $new = $ftpToLocalDirBkpSrc."/".$matches[0];
                                $ftpRename = ftp_rename($ftpHandle,$old,$new );
                                if ($ftpRename == true){
                                    $fileBackuped++;
                                    $msg = "\e[32;1mle fichier '".$matches[0]."' a ete archive.\e[0m\n";
                                    if ($verbose == true){
                                        echo $msg;
                                    }
                                    $msg = "le fichier '".$matches[0]."' a ete archive.\n";
                                    ($log == true) ? myLog($handle,"INFO: ",$msg) : "";
                                }else{
                                    $msg = "Une erreur est survenu lors de l'archivage du fichier '".$matches[0]."' .\n";
                                    if ($verbose == true){
                                        echo $warning.$msg;
                                    }
                                    ($log == true) ? myLog($handle,"WARNING: ",$msg) : "";
                                }
                            }
                        }else{
                            $msg = "Le transfert du fichier '".$matches[0]."' a echoue.\n";
                            if ($verbose == true){
                                echo $warning.$msg;
                            }
                            ($log == true) ? myLog($handle,"WARNING: ",$msg) : "";
                        }
                    }
                    unset($matches);
                }
                $msg = "Fin du telechargement des fichiers.\n";
                $resumeTreat = $fileTreated." fichier(s) ont ete telecharge(s) a l'emplacement suivant: ".$ftpToLocalDirDest."\n";
                $resumeBackup = $fileBackuped." fichier(s) ont ete archive(s) a l'emplacement suivant: ".$ftpToLocalDirBkpSrc."\n";
                echo $msg;
                echo $resumeTreat;
                echo $resumeBackup;
                ($log == true) ? myLog($handle,"INFO: ",$msg) : "";
                ($log == true) ? myLog($handle,"INFO: ",$resumeTreat) : "";
                ($log == true) ? myLog($handle,"INFO: ",$resumeBackup) : "";
            }else{
                $msg = "un probleme est survenu lors du listing des fichiers sur le serveur.\n";
                if ($verbose == true){
                    echo $warning.$msg;
                    echo $usage.$ftpListingMsg;
                }
                ($log == true) ? myLog($handle,"WARNING: ",$msg) : "";
            }

            // On ferme la connexion ftp et termine le script
            closeFtp($ftpHandle,$ftpToLocalHost,"",$verbose,$log,$handle);
            echo "Fin des traitements.\n";
            ($log == true) ? myLog($handle,"END") : "";
            break;
        case "localtoftp":
            $msg = "\t\e[34;1mMODE:\e[0m  ".$mode."\n";
            echo $msg;
            $msg = "MODE:  ".$mode."\n";
            ($log == true) ? myLog($handle,"INFO: ",$msg) : "";

            //recuperation des parametres de connection de la destination
            if((array_key_exists("hostDest", $paramConfig)) && (strlen($paramConfig["hostDest"]) > 0)){
                $localToFtpHostDest = $paramConfig["hostDest"];
            }else{
                echo $error.$hostDestErrorMsg;
                echo $usage.$paramConfUsage;
                ($log == true) ? myLog($handle,"ERROR",$hostDestErrorMsg) : "";
                break;
            }
            if((array_key_exists("loginDest", $paramConfig)) && (strlen($paramConfig["loginDest"]) > 0)){
                $localToFtpLoginDest = $paramConfig["loginDest"];
            }else{
                echo $error.$loginDestErrorMsg;
                echo $usage.$paramConfUsage;
                ($log == true) ? myLog($handle,"ERROR",$loginDestErrorMsg) : "";
                break;
            }
            if((array_key_exists("mdpDest", $paramConfig)) && (strlen($paramConfig["mdpDest"]) > 0)){
                $localToFtpMdpDest = $paramConfig["mdpDest"];
            }else{
                echo $error.$mdpDestErrorMsg;
                echo $usage.$paramConfUsage;
                ($log == true) ? myLog($handle,"ERROR",$mdpDestErrorMsg) : "";
                break;
            }

            //Connexions et Identifications aux FTP destination
            $ftpHandleDest = connectToFtp($localToFtpHostDest,"de destination",$verbose, $log,$handle, $error,$info);
            if($ftpHandleDest === false){
                break;
            }
            if (signToInFtp($ftpHandleDest,$localToFtpHostDest,$localToFtpLoginDest,$localToFtpMdpDest,$verbose,$log,$handle,$error) == false){
                break;
            }
            echo $info.$ftpEntryPointMsg.ftp_pwd($ftpHandleDest). "'\n";

            //recuperation des parametre sur les repertoires
            if ((array_key_exists("dirDest", $paramConfig)) && (strlen($paramConfig["dirDest"]) > 0)){
                $localToFtpDirDest = $paramConfig["dirDest"];
            }else{
                echo $error.$dirDestErrorMsg;
                echo $usage.$paramConfUsage;
                ($log == true) ? myLog($handle,"ERROR",$dirDestErrorMsg) : "";
                break;
            }
            if ((array_key_exists("dirLocal", $paramConfig)) && (strlen($paramConfig["dirLocal"]) > 0)){
                $localToFtpDirSrc =  $paramConfig["dirLocal"];
            }else{
                echo $error.$dirLocalErrorMsg;
                echo $usage.$paramConfUsage;
                ($log == true) ? myLog($handle,"ERROR",$dirLocalErrorMsg) : "";
                break;
            }
            if ((array_key_exists("dirBkpLocal", $paramConfig)) && (strlen($paramConfig["dirBkpLocal"]) > 0)){
                $localToFtpDirBkpSrc = $paramConfig["dirBkpLocal"];
            }else{
                echo $error.$dirBkpLocalErrorMsg;
                echo $usage.$paramConfUsage;
                ($log == true) ? myLog($handle,"ERROR",$dirBkpLocalErrorMsg) : "";
                break;
            }
            if ((array_key_exists("regex", $paramConfig)) && (strlen($paramConfig["regex"]) > 0)){
                $regexDefault =  $paramConfig["regex"];
            }else{
                echo $error.$regexErrorMsg;
                echo $usage.$paramConfUsage;
                ($log == true) ? myLog($handle,"ERROR",$regexErrorMsg) : "";
                break;
            }

            // Listing des fichiers en local
            $msg = "Listing des fichiers a traiter sur le repertoire local: ".$localToFtpDirSrc."\n";
            if ($verbose == true){
                echo $msg;
            }
            ($log == true) ? myLog($handle,"INFO: ",$msg) : "";
           if (is_dir($localToFtpDirSrc) == false){
               $msg = "Le chemin specifie ne correspond pas a un repertoire.\n";
               echo $warning.$msg;
               ($log == true) ? myLog($handle,"WARNING: ",$msg) : "";
           }else{
               $fileListToSend = scandir($localToFtpDirSrc);
               if (count($fileListToSend) <= 2){
                   $msg = "Le repertoire specifie '".$localToFtpDirSrc."'est vide.\n";
                   echo $info.$msg;
                   ($log == true) ? myLog($handle,"INFO: ",$msg) : "";
               }else{
                   $flagBkp = false;
                   $msgUpload = "Debut de l'upload des fichiers en local sur l'emplacement suivant: ".$localToFtpDirSrc."\n";
                   $msgBackup = "Creation du dossier d'archivage sur l'emplacement suivant: ".$localToFtpDirBkpSrc."\n";
                   echo $msgUpload;
                   if ($verbose == true){
                       echo $msgBackup;
                   }
                   ($log == true) ? myLog($handle,"INFO: ",$msgUpload) : "";
                   ($log == true) ? myLog($handle,"INFO: ",$msgBackup) : "";

                   //Verification et creation du dossier d'archivage en local
                   if (is_dir($localToFtpDirBkpSrc) == false){
                       $bkp = mkdir($localToFtpDirBkpSrc);
                       if ($bkp == false){
                           $flagBkp = true;
                           $msg = "Un probleme est survenu lors de la creation du repertoire de Backup.\n";
                           if ($verbose == true){
                               echo $warning.$msg;
                           }
                           ($log == true) ? myLog($handle,"WARNING: ",$msg) : "";
                       }
                   }else{
                       if ($verbose == true){
                           echo $info.$existingBkpDirMsg;
                       }
                       ($log == true) ? myLog($handle,"INFO: ",$existingBkpDirMsg) : "";
                   }

                   //Upload et Archivage
                   $fileTreatedDest = 0;
                   $fileBackuped = 0;
                   unset($fileListToSend[0]);
                   unset($fileListToSend[1]);
                   $fileListToSend = array_values($fileListToSend);
                   foreach($fileListToSend as $fileToSend){
                       preg_match($regexDefault, $fileToSend, $matches);
                       if (isset($matches[0])){
                           $remoteFile = $localToFtpDirDest."/".$matches[0];
                           $localFile = $localToFtpDirSrc."/".$matches[0];
                           $res = ftp_put($ftpHandleDest, $remoteFile, $localFile, FTP_BINARY);
                           if ($res == true){
                               $fileTreatedDest++;
                               $msg = "\e[32;1mLe transfert du fichier '".$matches[0]."' sur le FTP de destination a reussi.\e[0m\n";
                               if ($verbose == true){
                                   echo $msg;
                               }
                               $msg = "Le transfert du fichier '".$matches[0]."' sur le FTP de destination a reussi.\n";
                               ($log == true) ? myLog($handle,"INFO: ",$msg) : "";

                               //ici on archive le fichier envoye
                               if ($flagBkp == true){
                                   $msg = "le fichier '".$matches[0]."' n'a pas pu etre archive.\n";
                                   if ($verbose == true){
                                       echo $warning.$msg;
                                   }
                                   ($log == true) ? myLog($handle,"INFO: ",$msg) : "";
                               }else{
                                   $old = $localToFtpDirSrc."/".$matches[0];
                                   $new = $localToFtpDirBkpSrc."/".$matches[0];
                                   $rename = rename($old,$new);
                                   if ($rename == true){
                                       $fileBackuped++;
                                       $msg = "\e[32;1mle fichier '".$matches[0]."' a ete archive en local.\e[0m\n";
                                       if ($verbose == true){
                                           echo $msg;
                                       }
                                       $msg = "le fichier '".$matches[0]."' a ete archive en local.\n";
                                       ($log == true) ? myLog($handle,"INFO: ",$msg) : "";
                                   }else{
                                       $msg = "Une erreur est survenu lors de l'archivage du fichier '".$matches[0]."' .\n";
                                       if ($verbose == true){
                                           echo $warning.$msg;
                                       }
                                       ($log == true) ? myLog($handle,"WARNING: ",$msg) : "";
                                   }
                               }
                           }
                       }
                       unset($matches);
                   }
                   $msg = "Fin de l'upload des fichiers du local vers le FTP.\n";
                   $resumeTreatSrc = $fileTreatedDest." fichier(s) ont ete uploade(s) a l'emplacement suivant: ".$localToFtpDirDest."\n";
                   $resumeBackup = $fileBackuped." fichier(s) ont ete archive(s) a l'emplacement suivant: ".$localToFtpDirBkpSrc."\n";
                   echo $msg;
                   echo $resumeTreatSrc;
                   echo $resumeBackup;
                   ($log == true) ? myLog($handle,"INFO: ",$msg) : "";
                   ($log == true) ? myLog($handle,"INFO: ",$resumeTreatSrc) : "";
                   ($log == true) ? myLog($handle,"INFO: ",$resumeBackup) : "";
               }
           }

            // On ferme la connexion ftp et termine le script
            closeFtp($ftpHandleDest,$localToFtpHostDest,"de destination",$verbose,$log,$handle);
            echo "Fin des traitements.\n";
            ($log == true) ? myLog($handle,"END") : "";
            break;
        default:
            $msg = "Le mode ".$paramTab["mode"]." n'est pas reconnu.\n";
            echo $invalidArgumentMsg.$msg;
            echo $usage.$modeExMsg;
            ($log == true) ? myLog($handle,"ERROR",$msg) : "";
    }
}
else{
    $msg = "Le parametre et/ou l'option --mode ne sont pas defini(s).\n";
    ($log == true) ? myLog($handle,"ERROR",$msg) : "";
    echo $invalidOptionMsg.$msg;
    echo $usage.$launchExMsg;
    return;
}


/**
 * *********** Fonctions *********************
 */

/**
 * MyLog
 * Logs les msg dans un fichier
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
 * @param $shortOpt
 * @param $longOpt
 * @return array
 */
function myGetOpt($nbparam, $tabparam){
//    $res = getopt($shortOpt, $longOpt);
//    return $res;

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
 * @param $handle
 * @param $rep
 * @return bool
 */
function isFtpDirExist($handle, $rep){
    $res = ftp_nlist($handle, $rep);
//    var_dump($res);
    if ($res === false){
        return false;
    }else{
        return true;
    }
}

/**
 * SignInToFtp
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
        echo $msg;
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
                    $res[trim($tmp[0])] = (bool)trim($tmp[1]);
                }elseif(trim($tmp[0]) == "verbose"){
                    $res[trim($tmp[0])] = (bool)trim($tmp[1]);
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
