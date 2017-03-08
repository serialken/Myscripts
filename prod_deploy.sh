#!/bin/bash

# Environnement de l'application
APP_ENV=prod

#URL du VHost de DEV
APP_PREPROD_URL=http://mroad.resdom.amaury.local
APP_PREPROD_IP=10.150.11.10

# Configuration de l'envoi d'email
LOG_MAIL_ENABLED=1
#LOG_MAIL_ADDRESS=AMS-IT-MROAD@amaury.com
LOG_MAIL_ADDRESS=marcantoine.adelise@amaury.com
LOG_MAIL_SUBJECT='[MRoad] Déploiement en production'
LOG_MAIL_BODY=""

# Droits sur les fichiers
APP_RIGHTS_MOD=775
APP_RIGHTS_OWNERS='mroad.apache'

# Répertoire racine l'application
ABS_APP_ROOT_FOLDER=/var/www/html/MRoad/
APP_BOOTSTRAP_PATH=$ABS_APP_ROOT_FOLDER'web/app.php'

# Répertoire racine l'application en pré-production
ABS_APP_PREPROD_ROOT_FOLDER=/var/www/html/MRoad_Preprod/

# Authentification sur GITHub en cas de dépassement du quota de l'API
GITHUB_OAUTH_TOKEN='50dfeb7221e0776dc73afd39d45c493eae2cbab8'

# Le dossier Vendor
VENDOR_FOLDER=$ABS_APP_ROOT_FOLDER'vendor'
VENDOR_BACKUP_1_FOLDER=$VENDOR_FOLDER'.old'

echo "Déploiement d'MRoad en production..."

echo "Synchronisation des des sources de la pré-production"

# Répertoire racine
rsync -rvzog --progress --exclude="app/" --exclude="bin/" --exclude="sql/" --exclude="src/" --exclude="web/" $ABS_APP_PREPROD_ROOT_FOLDER $ABS_APP_ROOT_FOLDER

# App
rsync -rvzog --progress --exclude="cache/" --exclude="logs" --exclude="sessions" --delete $ABS_APP_PREPROD_ROOT_FOLDER"app" $ABS_APP_ROOT_FOLDER 

# Bin
rsync -rvzog --progress --delete $ABS_APP_PREPROD_ROOT_FOLDER"bin" $ABS_APP_ROOT_FOLDER

# SQL
rsync -rvzog --progress --delete $ABS_APP_PREPROD_ROOT_FOLDER"sql" $ABS_APP_ROOT_FOLDER

# SRC
rsync -rvzog --progress --delete $ABS_APP_PREPROD_ROOT_FOLDER"src" $ABS_APP_ROOT_FOLDER

#Web
rsync -rvzog --progress --delete --exclude="uploads/" $ABS_APP_PREPROD_ROOT_FOLDER"web" $ABS_APP_ROOT_FOLDER


# Suppression du vendor.old de la préprod
 if [ -d $ABS_APP_ROOT_FOLDER"vendor.old" ]; then
 	rm -rf $ABS_APP_ROOT_FOLDER"vendor.old"
 fi

#test sur le dossier vendor pour archivage
if [ -d $VENDOR_FOLDER ]; then
	echo $VENDOR_FOLDER existe, il va être archivé...
        LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n$VENDOR_FOLDER existe, il va être archivé....")

	# Suppression de l'ancienne archive
	if [ -d $VENDOR_BACKUP_1_FOLDER ]; then
                rm -rf $VENDOR_BACKUP_1_FOLDER
        fi

	mv $VENDOR_FOLDER $VENDOR_BACKUP_1_FOLDER
fi

# On se place dans le dossier de l'application
cd $ABS_APP_ROOT_FOLDER

# Dossier des sessions
if [ ! -d $ABS_APP_ROOT_FOLDER"app/sessions" ]; then
        echo "Le dossier des sessions n'existe pas, il va être créé..."
        mkdir $ABS_APP_ROOT_FOLDER"app/sessions"
fi

echo "************************************ COMPOSER **********************************************"
export http_proxy=http://10.196.20.193:80
php composer.phar config -g github-oauth.github.com $GITHUB_OAUTH_TOKEN
php composer.phar self-update
php composer.phar install
php composer.phar dump-autoload --optimize

LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n Exécution de l'auto MAJ de Composer et de l'optimisation de l'autoload...")


# echo "************************************ Remplacement des URL pour la recette ******************************************"

# Suppression des fichiers app_dev.php et app_prod.php puis remplacement par un lien symbolique vers app.php
if [ ! -h  $ABS_APP_ROOT_FOLDER"web/app_dev.php" ]; then
	rm $ABS_APP_ROOT_FOLDER"web/app_dev.php"
	ln -s $ABS_APP_ROOT_FOLDER"web/app.php" $ABS_APP_ROOT_FOLDER"web/app_dev.php"
fi

if [ ! -h  $ABS_APP_ROOT_FOLDER"web/app_preprod.php" ]; then
	rm $ABS_APP_ROOT_FOLDER"web/app_preprod.php"
	ln -s $ABS_APP_ROOT_FOLDER"web/app.php" $ABS_APP_ROOT_FOLDER"web/app_preprod.php"
fi

if [ -f  $ABS_APP_ROOT_FOLDER"web/app_prod.php" ]; then
	rm $ABS_APP_ROOT_FOLDER"web/app.php"
	mv $ABS_APP_ROOT_FOLDER"web/app_prod.php" $ABS_APP_ROOT_FOLDER"web/app.php"
fi

echo "Affectation des bons droits"
chmod -R $APP_RIGHTS_MOD $ABS_APP_ROOT_FOLDER
chown -R $APP_RIGHTS_OWNERS $ABS_APP_ROOT_FOLDER

echo "Affectation des droits sur les fichiers de session"
chown mroad.apache $ABS_APP_ROOT_FOLDER"app/sessions"
chown apache.apache $ABS_APP_ROOT_FOLDER"app/sessions/"*
chmod 600 $ABS_APP_ROOT_FOLDER"app/sessions/"*

echo "************************************ ASSETIC DUMP ******************************************"
php app/console assetic:dump --env $APP_ENV

echo "************************************ MAJ DE LA NAVIGATION ******************************************"
php app/console navigation_update --env $APP_ENV

echo "*********************************** SUPPRESSION DU CACHE ***********************************"
rm -rf app/cache/*
php app/console cache:clear --env $APP_ENV

echo "Affectation des bons droits sur le dossier de cache"
chown -R mroad.apache app/cache/$APP_ENV
chmod -R 770 app/cache/$APP_ENV

echo "*********************************** RECHARGEMENT D'APACHE ***********************************"
sudo /etc/init.d/httpd reload

echo "*********************************** DOCTRINE ***********************************"
LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n Vérifications de Doctrine...")
php app/console doctrine:schema:validate
VERIF_DOCTRINE=$(php app/console doctrine:schema:validate)
LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n\n$VERIF_DOCTRINE\n\n")
# Doctrine Schema update
php app/console doctrine:schema:update --env prod
php app/console doctrine:schema:update --dump-sql --env prod
DOCTRINE_UPDATE=$(php app/console doctrine:schema:update --env prod)
DOCTRINE_UPDATE_SQL=$(php app/console doctrine:schema:update --dump-sql --env prod)
LOG_MAIL_BODY=$(echo -e "$LOG_MAIL_BODY\n\n $DOCTRINE_UPDATE_SQL\n")


# Envoi final de l'e-mail
if [ $LOG_MAIL_ENABLED == 1 ]; then
        echo -e "$LOG_MAIL_BODY" | mail -s "$LOG_MAIL_SUBJECT" $LOG_MAIL_ADDRESS
fi


echo " ******************************** HORODATAGE DU DEPLOIEMENT ********************************* "
# Nom du fichier de version
VERSION_TXT_FILE=web/mroad_version.txt

VERSION_TXT_CONTENU=$(cat $VERSION_TXT_FILE)
SVN_REV="$( cut -d '|' -f 1 <<< "$VERSION_TXT_CONTENU" )";

DATE_EXEC=$(date +%Y%m%d%H%M%S)
echo $SVN_REV"|"$DATE_EXEC > $VERSION_TXT_FILE


echo "********************************** FIN DES OPERATIONS DE LIVRAISON ***************************"
echo Le déploiement en production est effectué.

echo "********************************* TEST SUR L'ENVIRONNEMENT ***********************************"
if grep --quiet "new AppKernel('$APP_ENV'" $APP_BOOTSTRAP_PATH; then
        echo "Le fichier "$APP_BOOTSTRAP_PATH" charge bien l'environnement "$APP_ENV
else
        echo "Il semble y avoir un problème sur l'environnement chargé par app.php. Veuillez vérifier que l'environnement "$APP_ENV" est bien chargé par "$APP_BOOTSTRAP_PATH" Voici son contenu:\n"
        cat $APP_BOOTSTRAP_PATH
fi

