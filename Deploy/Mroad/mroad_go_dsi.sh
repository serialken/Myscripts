#!/bin/bash

# Environnement de l'application
APP_ENV=recette

# Répertoire racine des versions de l'application
ABS_DEV_APP_ROOT_FOLDER=/home/dev-mroad/mroad-app/releases/current/
ABS_DSI_APP_ROOT_FOLDER=/home/silog/public_html/MRoad.new/

# Authentification sur GITHub en cas de dépassement du quota de l'API
GITHUB_OAUTH_TOKEN='50dfeb7221e0776dc73afd39d45c493eae2cbab8'

# Répertoire des fichiers générés par l'application
ABS_UGC_FOLDER=/home/silog/public_html/MRoad/web/uploads/

# Droits sur les fichiers
APP_RIGHTS_MOD=755
APP_RIGHTS_OWNERS='silog.silog'

echo Récupération de la dernière version de MRoad déployée en Dev sur $ABS_DEV_APP_ROOT_FOLDER ...

# Test sur le dossier cible
if [ -d $ABS_DSI_APP_ROOT_FOLDER ]; then
        echo $ABS_DSI_APP_ROOT_FOLDER va être supprimé et re-créé...
        rm -rf $ABS_DSI_APP_ROOT_FOLDER
	mkdir $ABS_DSI_APP_ROOT_FOLDER

        if [ -d $ABS_DSI_APP_ROOT_FOLDER ]; then
                echo Le dossier  $ABS_DSI_APP_ROOT_FOLDER a bien été créé
        else
                echo "ERREUR! Le dossier  $ABS_DSI_APP_ROOT_FOLDER n'a pas été créé!"
                echo "******************** Fin d'exécution du programme ******************"
                exit
        fi
else
	echo Le dossier $ABS_DSI_APP_ROOT_FOLDER n\'existe pas... il sera créé
	rm -rf $ABS_DSI_APP_ROOT_FOLDER
        mkdir $ABS_DSI_APP_ROOT_FOLDER

        if [ -d $ABS_DSI_APP_ROOT_FOLDER ]; then
                echo Le dossier  $ABS_DSI_APP_ROOT_FOLDER a bien été créé
        else
                echo "ERREUR! Le dossier  $ABS_DSI_APP_ROOT_FOLDER n'a pas été créé!"
                echo "******************** Fin d'exécution du programme ******************"
                exit
        fi
fi

echo Copie en cours...

cp -Rv $ABS_DEV_APP_ROOT_FOLDER* $ABS_DSI_APP_ROOT_FOLDER

echo Suppression du dossier Vendor ...
rm $ABS_DSI_APP_ROOT_FOLDER"vendor"

echo Suppression du dossier bin ...
rm -rf $ABS_DSI_APP_ROOT_FOLDER"bin"
echo Création du dossier BIN
mkdir $ABS_DSI_APP_ROOT_FOLDER"bin"

#echo Copie des fichiers de BIN
#cp -Rv $ABS_DEV_APP_ROOT_FOLDER"bin/"* $ABS_DSI_APP_ROOT_FOLDER"bin/"

echo Suppression du dossier uploads ...
rm  $ABS_DSI_APP_ROOT_FOLDER"web/uploads"

echo Création des dossiers Vendor et Uploads vides ...
mkdir $ABS_DSI_APP_ROOT_FOLDER"vendor"
mkdir $ABS_DSI_APP_ROOT_FOLDER"web/uploads"
mkdir $ABS_DSI_APP_ROOT_FOLDER"web/tmp"

#echo Copie des Vendors de la Dev...
#cp -Rv $ABS_DEV_APP_ROOT_FOLDER"vendor/"* $ABS_DSI_APP_ROOT_FOLDER"vendor/"

echo Copie du contenu des uploads de la recette utilisateurs...
cp -Rv $ABS_UGC_FOLDER* $ABS_DSI_APP_ROOT_FOLDER"web/uploads/"

echo Suppression du cache
rm -rf $ABS_DSI_APP_ROOT_FOLDER"app/cache/"*
cd $ABS_DSI_APP_ROOT_FOLDER


echo "************************************ COMPOSER **********************************************"
export http_proxy=http://10.196.20.193:80
php composer.phar config -g github-oauth.github.com $GITHUB_OAUTH_TOKEN
php composer.phar self-update
php composer.phar install
php composer.phar dump-autoload --optimize


# echo "************************************ Remplacement des URL pour la recette ******************************************"
cp $ABS_DSI_APP_ROOT_FOLDER"web/app_recette.php" $ABS_DSI_APP_ROOT_FOLDER"web/app_devtest.php"
rm -f $ABS_DSI_APP_ROOT_FOLDER"web/app_dev.php"
rm -f $ABS_DSI_APP_ROOT_FOLDER"web/app.php"
ln -s $ABS_DSI_APP_ROOT_FOLDER"web/app_recette.php" $ABS_DSI_APP_ROOT_FOLDER"web/app_dev.php"
ln -s $ABS_DSI_APP_ROOT_FOLDER"web/app_recette.php" $ABS_DSI_APP_ROOT_FOLDER"web/app.php"

echo "************************************ ASSETIC DUMP ******************************************"
php app/console assetic:dump --env $APP_ENV

echo "************************************ MAJ DE LA NAVIGATION ******************************************"
php app/console navigation_update --env $APP_ENV

echo "*********************************** SUPPRESSION DU CACHE ***********************************"
php app/console cache:clear --env $APP_ENV
rm -rf app/cache/*

# Doctrine Schema update
php app/console doctrine:schema:update --env $APP_ENV
php app/console doctrine:schema:update --dump-sql --env $APP_ENV

echo "Affectation des bons droits"
chmod -R $APP_RIGHTS_MOD $ABS_DSI_APP_ROOT_FOLDER
chown -R $APP_RIGHTS_OWNERS $ABS_DSI_APP_ROOT_FOLDER

# Horodatage du déploiement
echo "Horodatage du dpéloiement pour la page 'A propos'"
# Nom du fichier de version
VERSION_TXT_FILE=web/mroad_version.txt

VERSION_TXT_CONTENU=$(cat $VERSION_TXT_FILE)
SVN_REV="$( cut -d '|' -f 1 <<< "$VERSION_TXT_CONTENU" )";

DATE_EXEC=$(date +%Y%m%d%H%M%S)
echo $SVN_REV"|"$DATE_EXEC > $VERSION_TXT_FILE


echo "********************************** FIN DES OPERATIONS DE LIVRAISON ***************************"
echo La livraison en recette DSI est effectuée.
echo Veillez à effectuer les changements suivants:
# echo "1/ Modifier app/config/mroad.ini"
# echo "2/ Modifier app/config/parameters_dev.yml"
# echo "3/ Faites un assetic:dump"
echo "1/ Vérifiez qu'il n'y a pas de delta entre la BDD de Dev et celle de Recette"
echo "2/ Testez sur l'environnement de recette DSI"
echo "3/ Testez encore et encore !"

