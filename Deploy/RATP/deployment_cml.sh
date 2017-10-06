#!/bin/sh

echo Début du déploiement sur dev-Bacasable-recette

dev_path=/usr/local/apache2/htdocs/dev
rec_path=/usr/local/apache2/htdocs/recette
bac_path=/usr/local/apache2/htdocs/bacasable
archi_path=/usr/local/apache2/htdocs/dev/archives_deliveries

tar -cvzf $1.tar.gz $1

mv $1.tar.gz $archi_path

cp -r $1 $dev_path/release/
cp -r $1 $rec_path/release/
cp -r $1 $bac_path/release/

ln -s "$dev_path/shared/export" $dev_path/release/$1/export
ln -s "$rec_path/shared/export" $rec_path/release/$1/export
ln -s "$bac_path/shared/export" $bac_path/release/$1/export

ln -s "$dev_path/shared/log" $dev_path/release/$1/log
ln -s "$rec_path/shared/log" $rec_path/release/$1/log
ln -s "$bac_path/shared/log" $bac_path/release/$1/log

rm -f $dev_path/current/include/CHAINE_CONNEXION.INC
rm -f $rec_path/current/include/CHAINE_CONNEXION.INC
rm -f $bac_path/current/include/CHAINE_CONNEXION.INC

rm -f $dev_path/release/$1/include/CHAINE_CONNEXION.INC
rm -f $rec_path/release/$1/include/CHAINE_CONNEXION.INC
rm -f $bac_path/release/$1/include/CHAINE_CONNEXION.INC

ln -s "$dev_path/shared/CHAINE_CONNEXION.INC" $dev_path/release/$1/include/CHAINE_CONNEXION.INC
ln -s "$rec_path/shared/CHAINE_CONNEXION.INC" $rec_path/release/$1/include/CHAINE_CONNEXION.INC
ln -s "$bac_path/shared/CHAINE_CONNEXION.INC" $bac_path/release/$1/include/CHAINE_CONNEXION.INC

rm -f $dev_path/current
rm -f $rec_path/current
rm -f $bac_path/current

ln -s "$dev_path/release/$1" $dev_path/current
ln -s "$bac_path/release/$1" $bac_path/current
ln -s "$rec_path/release/$1" $rec_path/current

echo Fin du déploiement sur dev-Bacasable-recette
echo Activez le mode debug sur la dev
echo Modifiez le nom de l'archive
