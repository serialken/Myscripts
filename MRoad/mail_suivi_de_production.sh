#!/bin/bash

########## Envoi d'un mail lors de l'integration du 1er fichier non vide
############################### Ce CRON doit tourner toutes les 7 mn pour une nuit de production
################################################# EX: DE 22h00 A  23h45



# ---------- Parametrage global sh

# Environnement
env='prod'

# identifiant du sh
id_sh='mail_suivi_de_production'

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



echo "Envoi Mail fichier suivi de production"

# ---------- Liste des taches a effectuer ----------

#*-*-*-*-*- Controle et envoi du mail
php app/console sendmail_suivi_production --env=$env > $fic_log 2>&1
cat $fic_log

# --------------- Fin SH -------------------