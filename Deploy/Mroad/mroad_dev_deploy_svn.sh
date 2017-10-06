#!/bin/bash
# URL du dépot SVN
SVN_REPO_URL=svn://10.150.5.25/mroad

#URL du VHost de DEV
APP_DEV_URL=http://dev-mroad.sdv/web/app_dev.php
APP_DEV_IP=10.150.5.156

if [ "$#" == 0 ]; then
		# Récupération du numéro de la dernière révision
		SVN_LAST_REV=$(svn info -rHEAD $SVN_REPO_URL | grep "Révision" | awk '{print $2}' | head -1)
		SVN_MODE='FW'
	else
		SVN_LAST_REV=$1
		SVN_MODE='RB'
fi

# Configuration de l'envoi d'email
LOG_MAIL_ENABLED=1
LOG_MAIL_ADDRESS=AMS-IT-MROAD@amaury.com
#LOG_MAIL_ADDRESS=marcantoine.adelise@amaury.com
LOG_MAIL_SUBJECT='Déploiement en DEV de MRoad rév. '$SVN_LAST_REV' ('$SVN_MODE')'
LOG_MAIL_BODY=""

# Répertoire racine des versions de l'application
ABS_APP_ROOT_FOLDER=/home/dev-mroad/mroad-app/releases/
APP_ROOT_FOLDER=../mroad-app/releases/

# Nom du fichier de version
VERSION_TXT_FILE=mroad_version.txt

# Répertoire des dossiers partagés
ABS_SHARED_FOLDER=/home/dev-mroad/mroad-app/shared/
APP_SHARED_FOLDER=../mroad-app/shared/

# Authentification sur GITHub en cas de dépassement du quota de l'API
GITHUB_OAUTH_TOKEN='50dfeb7221e0776dc73afd39d45c493eae2cbab8' 

# Les différents dossiers Vendor
VENDOR_FOLDER=$ABS_SHARED_FOLDER'vendor'
VENDOR_BACKUP_1_FOLDER=$VENDOR_FOLDER'.old'
VENDOR_BACKUP_2_FOLDER=$VENDOR_FOLDER'.vold'

# Nom du lien symbolique vers la version courante
APP_SYMLINK_NAME='current'

# Création du nom du dossier
DATE_EXEC=$(date +%Y%m%d%H%M%S)
APP_NEWREV_FOLDER=$SVN_LAST_REV-$DATE_EXEC
APP_NEWREV_DIR_NAME=$APP_ROOT_FOLDER$APP_NEWREV_FOLDER

#Log par mail
#LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\nLe $(date +%d/%m/%Y à %H%:M%:S)")
LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_SUBJECT  \n")

# Droits sur les fichiers
APP_RIGHTS_MOD=755
APP_RIGHTS_OWNERS='dev-mroad.dev-mroad'

echo Récupération de la révision $SVN_LAST_REV depuis $SVN_REPO_URL pour export dans le dossier $APP_NEWREV_DIR_NAME
LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\nRécupération de la révision $SVN_LAST_REV depuis $SVN_REPO_URL pour export dans le dossier $APP_NEWREV_DIR_NAME")

# Test sur le dossier racine
if [ ! -d $APP_ROOT_FOLDER ]; then
	echo $APP_ROOT_FOLDER doit être créé...
	LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n$APP_ROOT_FOLDER doit être créé...")
	mkdir $APP_ROOT_FOLDER

	if [ -d $APP_ROOT_FOLDER ]; then
		echo Le dossier  $APP_ROOT_FOLDER a bien été créé
		$LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\nLe dossier  $APP_ROOT_FOLDER a bien été créé")
	else
		echo "ERREUR! Le dossier  $APP_ROOT_FOLDER n'a pas été créé!"
		LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\nERREUR! Le dossier  $APP_ROOT_FOLDER n'a pas été créé!")
		echo "******************** Fin d'exécution du programme ******************"
		LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n******************** Fin d'exécution du programme ******************")

		if [ $LOG_MAIL_ENABLED == 1 ]; then
			echo -e "$LOG_MAIL_BODY" | mail -a "Content-Type: text/plain; charset=UTF-8" -s "$LOG_MAIL_SUBJECT" $LOG_MAIL_ADDRESS
		fi
		exit
	fi
else
 echo Le dossier $APP_ROOT_FOLDER existe bien.
  LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n Le dossier $APP_ROOT_FOLDER existe bien.")
fi

# Test sur le dossier vendor
if [ ! -d $VENDOR_FOLDER ]; then
        echo $VENDOR_FOLDER doit être créé...
        LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n$VENDOR_FOLDER doit être créé.")
        mkdir $ABS_SHARED_FOLDER"vendor"

        if [ -d $VENDOR_FOLDER ]; then
                echo Le dossier $VENDOR_FOLDER a bien été créé
                LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY \n Le dossier $VENDOR_FOLDER a bien été créé")
        else
                echo "ERREUR! Le dossier $VENDOR_FOLDER n'a pas été créé!"
                LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\nERREUR! Le dossier $VENDOR_FOLDER n'a pas été créé!")
                echo "******************** Fin d'exécution du programme ******************"
                LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n******************** Fin d'exécution du programme ******************")

                if [ $LOG_MAIL_ENABLED == 1 ]; then
                        echo -e "$LOG_MAIL_BODY" | mail -a "Content-Type: text/plain; charset=UTF-8" -s "$LOG_MAIL_SUBJECT" $LOG_MAIL_ADDRESS
                fi
                exit
        fi
else
 	echo Le dossier $VENDOR_FOLDER existe bien. Il sera archivé et remplacé
	LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n Le dossier $VENDOR_FOLDER existe bien. Il sera archivé et remplacé")

	if [ -d $VENDOR_BACKUP_2_FOLDER ]; then
		rm -rf $VENDOR_BACKUP_2_FOLDER
	fi

	if [ -d $VENDOR_BACKUP_1_FOLDER ]; then
		mv $VENDOR_BACKUP_1_FOLDER $VENDOR_BACKUP_2_FOLDER
	fi

	mv $VENDOR_FOLDER $VENDOR_BACKUP_1_FOLDER

	mkdir $VENDOR_FOLDER
fi

# Création du dossier cible
echo Création du dossier $APP_NEWREV_DIR_NAME
LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n Création du dossier $APP_NEWREV_DIR_NAME")
mkdir $APP_NEWREV_DIR_NAME

if [ ! -d $APP_NEWREV_DIR_NAME ]; then
	 echo ERREUR! Le dossier  $APP_NEWREV_DIR_NAME n\'a pas été créé!
         LOG_MAIL_BODY=$( echo -e "$LOG_MAIL_BODY\n ERREUR! Le dossier  $APP_NEWREV_DIR_NAME n\'a pas été créé!")
         echo "******************** Fin d'exécution du programme ******************"
	 LOG_MAIL_BODY=$( echo -e "$LOG_MAIL_BODY\n******************** Fin d'exécution du programme ******************")
	
	if [ $LOG_MAIL_ENABLED == 1 ]; then
		echo -e "$LOG_MAIL_BODY" | mail -a "Content-Type: text/plain; charset=UTF-8" -s "$LOG_MAIL_SUBJECT" $LOG_MAIL_ADDRESS
	fi
	 exit
fi

echo Récupération des sources depuis le SVN ...
LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n Récupération des sources depuis le SVN ...")

svn export $SVN_REPO_URL $APP_NEWREV_DIR_NAME --force --non-interactive > /dev/null


cd $APP_ROOT_FOLDER
if [ -h $APP_SYMLINK_NAME ]; then 
	rm $APP_SYMLINK_NAME
fi

echo Création du lien symbolique vers $APP_NEWREV_DIR_NAME...
LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n Création du lien symbolique vers $APP_NEWREV_DIR_NAME...")

ln -s $APP_NEWREV_FOLDER $APP_SYMLINK_NAME

if [ -h $APP_SYMLINK_NAME ]; then
        echo Lien symbolique créé !
else
	echo ERREUR! Le lien symbolique n a pas pu être créé!
	LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n ERREUR! Le lien symbolique n a pas pu être créé!")
	echo "******************** Fin d'exécution du programme ******************"
	LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n ******************** Fin d'exécution du programme ******************")
        exit

fi 

echo Création des dossiers nécessaires à l application
LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n Création des dossiers nécessaires à l application\n")

FOLDERS_TO_CREATE=(app/logs app/cache web/tmp)
cd $APP_SYMLINK_NAME
for MISSING_DIR in ${FOLDERS_TO_CREATE[*]}
do
	if [ ! -d $MISSING_DIR ]; then
		echo Création de $MISSING_DIR ...
		LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n Création de $MISSING_DIR ...\n")
		mkdir -p $MISSING_DIR

		# Test de création
		if [ -d $MISSING_DIR ]; then
			echo "OK!"
			LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY OK!")
		else
			echo "Erreur! $MISSING_DIR n a pas pu être créé!"
			LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n Erreur! $MISSING_DIR n a pas pu être créé!")
			echo "******************** Fin d'exécution du programme ******************"
			LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n ******************** Fin d'exécution du programme ******************")
			if [ $LOG_MAIL_ENABLED == 1]; then
		                echo -e "$LOG_MAIL_BODY" | mail -a "Content-Type: text/plain; charset=UTF-8" -s "$LOG_MAIL_SUBJECT" $LOG_MAIL_ADDRESS
        		fi
		        exit
		fi

	fi
done

echo Suppression des dossiers non nécessaires
LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n Suppression des dossiers non nécessaires\n")

FOLDERS_TO_DELETE=(doc vendor web/uploads)
for UNNECESSARY_DIR in ${FOLDERS_TO_DELETE[*]}
do
        if [ -d $UNNECESSARY_DIR ]; then
                echo Supression de $UNNECESSARY_DIR...
		LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n Supression de $UNNECESSARY_DIR...\n")
                rm -rf $UNNECESSARY_DIR

                # Test de suppression
                if [ ! -d $UNNECESSARY_DIR ]; then
			LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY OK!")
                        echo "OK!"
                else
                        echo "Erreur! $UNNECESSARY_DIR n'a pas pu être supprimé!"
			LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n Erreur! $UNNECESSARY_DIR n'a pas pu être supprimé!")
                        echo "******************** Fin d'exécution du programme ******************"
			$LOG_MAIL_BODY=$( echo -e "$LOG_MAIL_BODY\n")

			if [ $LOG_MAIL_ENABLED == 1]; then
                                echo -e "$LOG_MAIL_BODY" | mail -a "Content-Type: text/plain; charset=UTF-8" -s "$LOG_MAIL_SUBJECT" $LOG_MAIL_ADDRESS
                        fi
                        exit
                fi

        fi
done

echo "Création des liens nécessaires à l'application"
LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n Création des liens nécessaires à l'application\n")

LINKS_TO_CREATE=(web/uploads vendor)
for SYM_LINK in ${LINKS_TO_CREATE[*]}
do
        if [ ! -h $SYM_LINK ]; then
                echo Création du lien $SYM_LINK...
		LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n Création du lien $SYM_LINK...\n")
                ln -s $ABS_SHARED_FOLDER$SYM_LINK $SYM_LINK

                # Test du lien
                if [ -h $SYM_LINK ]; then
                        echo "OK!"
			LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY OK!")
                else
                        echo "Erreur! Le lien $SYM_LINK n a pas pu être créé!"
			LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n Erreur! Le lien $SYM_LINK n a pas pu être créé!")
                        echo "******************** Fin d exécution du programme ******************"
			LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n ******************** Fin d exécution du programme ******************")
			if [ $LOG_MAIL_ENABLED == 1]; then
                                echo -e "$LOG_MAIL_BODY" | mail -a "Content-Type: text/plain; charset=UTF-8" -s "$LOG_MAIL_SUBJECT" $LOG_MAIL_ADDRESS
                        fi
                        exit
                fi

        fi
done

echo "***************************** CONVERSION DES FICHIERS SQL SUSPECTS ***********************"
SQL_FOLDER=$ABS_APP_ROOT_FOLDER$APP_SYMLINK_NAME"/sql"
for i in $(ls $SQL_FOLDER)
        do
                ENCODAGE=$(file $SQL_FOLDER"/"$i)
                if  echo $ENCODAGE | grep -q "UTF-8 Unicode (with BOM) text"; then
                        echo $i" doit être converti..."
                        awk '{ if (NR==1) sub(/^\xef\xbb\xbf/,""); print }' $SQL_FOLDER"/"$i > $SQL_FOLDER"/"$i"_tmp"
                        rm -f $SQL_FOLDER"/"$i
                        mv $SQL_FOLDER"/"$i"_tmp" $SQL_FOLDER"/"$i
                        if [ -f $SQL_FOLDER"/"$i ]; then
                                echo $i " a été converti!"
                        else
                                echo "Un problème a été rencontré lors de la conversion de "$i
                        fi
       		fi
	done


echo "******************************* ENREGISTREMENT DU FICHIER DE VERSION ***********************"
if [ ! -f  $ABS_APP_ROOT_FOLDER$APP_SYMLINK_NAME"/web/"$VERSION_TXT_FILE ]; then
	touch $ABS_APP_ROOT_FOLDER$APP_SYMLINK_NAME"/web/"$VERSION_TXT_FILE
fi
echo  $SVN_LAST_REV"|"$DATE_EXEC > $ABS_APP_ROOT_FOLDER$APP_SYMLINK_NAME"/web/"$VERSION_TXT_FILE

echo "************************************ COMPOSER **********************************************"
export http_proxy=http://10.196.20.193:80
php composer.phar config -g github-oauth.github.com $GITHUB_OAUTH_TOKEN
php composer.phar self-update
php composer.phar install
php composer.phar dump-autoload --optimize

LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n Exécution de l'auto MAJ de Composer et de l'optimisation de l'autoload...")


echo "************************************ ASSETIC DUMP ******************************************"
php app/console assetic:dump
LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n Assetic Dump...")


echo "************************************ MAJ DE LA NAVIGATION ******************************************"
php app/console navigation_update

echo "*********************************** SUPPRESSION DU CACHE ***********************************"
php app/console cache:clear
rm -rf app/cache/*
LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n Suppression des fichiers du cache")

echo "Affectation des bons droits"
chmod -R $APP_RIGHTS_MOD $ABS_APP_ROOT_FOLDER # A corriger ou préciser
chown -R $APP_RIGHTS_OWNERS $ABS_APP_ROOT_FOLDER

echo "*********************************** COPIE DE APP.PHP ****************************"
if [ ! -f $ABS_APP_ROOT_FOLDER$APP_SYMLINK_NAME"/web/app_prod.php" ]; then
	cp $ABS_APP_ROOT_FOLDER$APP_SYMLINK_NAME"/web/app.php" $ABS_APP_ROOT_FOLDER$APP_SYMLINK_NAME"/web/app_prod.php"
fi

echo "*********************************** DOCTRINE ***********************************"
LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n Vérifications de Doctrine...")
php app/console doctrine:schema:validate
VERIF_DOCTRINE=$(php app/console doctrine:schema:validate)
LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n\n$VERIF_DOCTRINE\n\n")
# Doctrine Schema update
php app/console doctrine:schema:update
php app/console doctrine:schema:update --dump-sql
DOCTRINE_UPDATE=$(php app/console doctrine:schema:update)
DOCTRINE_UPDATE_SQL=$(php app/console doctrine:schema:update --dump-sql)
LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n $DOCTRINE_UPDATE\n\n $DOCTRINE_UPDATE_SQL\n")

LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n Affectation des bons droits sur les fichiers de l'application...")
LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n Fin du déploiement de l'application MRoad rév. $SVN_LAST_REV")
LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n Cette version est maintenant disponible sur $APP_DEV_URL -> $APP_DEV_IP")
LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n\n Merci de vos tests et retours sur l'application.")
LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n Pensez à mettre à jour le fichier de suivi sur le x:\suivi\suivi_deploiements.xlsx\n\nLe robot de déploiement MRoad")

# Envoi final de l'e-mail
if [ $LOG_MAIL_ENABLED == 1 ]; then
	echo -e "$LOG_MAIL_BODY" | mail -a "Content-Type: text/plain; From: MRoad <root@sdvprdwebapp02.sdv.amaury.local>; charset=UTF-8" -s "$LOG_MAIL_SUBJECT" $LOG_MAIL_ADDRESS
fi
