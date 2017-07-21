#!/bin/bash

########## Import/Integration/Archivage des fichiers pour une nuit de production
############################### Ce CRON doit tourner toutes les 5 mn pour une nuit de production
################################################# EX: DE 20h00 A  09h45



# ---------- Parametrage global sh

# Environnement
env='prod'

# identifiant du sh
id_sh='suivi_de_production'

# racine scripts
racine_projet='/var/www/html/MRoad'

# racine fichiers a traiter
racine_fic_a_traiter='/var/www/html/MRoad'

# repertoire des CRON
fic_cron='/var/www/MRoad_Cron'

# sous repertoires des fichiers de log
ssrep_log='../MRoad_Fichiers/Logs'

# Fichiers de log
fic_log=$racine_fic_a_traiter'/'$ssrep_log'/sh_'$id_sh'_'$(date '+%Y%m%d_%H%M%S')'.sh.log'

cd $racine_projet



echo "Import/Integration/Archivage des fichiers pour une nuit de production"

# ---------- Liste des taches a effectuer ----------

#*-*-*-*-*- Import en Local des fichiers de production
echo "Import des fichiers depuis le FTP ... "
php app/console import_fic_ftp SUIVI_PRODUCTION --env=$env > $fic_log 2>&1
cat $fic_log


#*-*-*-*-*- Integration en BDD des fichiers de production
echo "Insertion/MAJ des fichiers en BDD ... "
php app/console suivi_de_production_integration  --env=$env > $fic_log 2>&1
cat $fic_log


#*-*-*-*-*- Archivage sur le FTP des fichiers de production
echo "Archivage sur le FTP des fichiers  deja integre ou mis a jour sur MROAD ... "
php $fic_cron/MyBkpSuiviProd.php --file=$fic_cron/bkpSuiviProd_prod.ini > $fic_log 2>&1
cat $fic_log


# --------------- Fin SH -------------------