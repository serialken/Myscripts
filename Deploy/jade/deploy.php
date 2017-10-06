<?php

require 'recipe/symfony.php';

/**
 * --- Deploiement de DAN JADE ---
 * 
 * Mise en place de "deployer" 
 * . Si machine windows
 *     - Installer une interface de lignes de commandes LINUX pour windows. Exemple : "cygwin"
 *
 * . Installation de "deployer"
 *     - Telecharger "deployer.phar"
 *     - mv deployer.phar /usr/local/bin/dep
 *     - chmod +x /usr/local/bin/dep
 * 
*/

/**
 * Lancement du deploiement
 * Se mettre dans le repertoire ou se trouve le fichier courant (deploy.php)
 *     - cd /cygdrive/c/wamp/www/Deploy/jade
 * Lancer la commande de deploiement
 *     - dep deploy <environnement>
 *         ou <environnement> peut prendre une des valeurs suivantes dev|rec|prod
*/

$nbArchiveTarGz=20;
$nbReleases=10;
$chmodTarGz=775;

serverList('servers.yml');
//set('repository', 'ssh://git@10.150.12.12/jade.git'); 
set('repository', 'ssh://git@{{serveur_git}}/jade.git');
set('keep_releases', 10);

set('key', 'value');


task('deploy:mv_config', function () {
	run('cp {{release_path}}/includes/config_local.inc.php {{release_path}}/includes/config_local_bkp.inc.php');
	run('cp {{release_path}}/includes/config_{{environ}}.inc.php {{release_path}}/includes/config_local.inc.php');
	write('Modification du fichier de config effectuee');
});


task('deploy:config_deploy', function () {
	// Recuperation du bon fichier de "config" selon l'environnement
	run('cp {{release_path}}/includes/config_deploy.inc.php {{release_path}}/includes/config_deploy_bkp.inc.php');
	run('echo -e "<?php\ndefine(\"DEPLOY_PATH\", \"{{deploy_path}}/\");\n" > {{release_path}}/includes/config_deploy.inc.php');
	
	// Ecrire {{deploy_path}} dans le fichier TXT {{deploy_path}}/deploy_path.txt
	run('echo -e "{{deploy_path}}" > {{deploy_path}}/deploy_path.txt');
	run('ln -s {{deploy_path}}/deploy_path.txt {{release_path}}/deploy_path.txt');
	write('Modification du fichier de config effectuee');
});


task('deploy:symlink_phpmyadmin', function () {
	run('ln -s {{deploy_path}}/phpmyadmin {{release_path}}/phpmyadmin');
	write('Creation du lien symbolique vers "phpmyadmin"');
});


task('deploy:symlink_info_php', function () {
	run('ln -s {{deploy_path}}/info.php {{release_path}}/info.php');
	write('Creation du lien symbolique vers "info.php"');
});



task('deploy:archive', function () {
	global $nbArchiveTarGz, $nbReleases, $chmodTarGz;	
	
	// Archivage du release courant	
	$current_release=run("readlink {{deploy_path}}/current")->toString();
	run('tar -zcvf '.$current_release.'.tar.gz '.$current_release.'');
	write('Archivage effectue!');
});


task('deploy:delete_archive_gz', function () {
	global $nbArchiveTarGz, $nbReleases, $chmodTarGz;	
	run('find {{deploy_path}}/releases/* -maxdepth 0 -type f | sort -r | tail -n +'.($nbArchiveTarGz+1).' | xargs -I{} -r rm -rf {}');
	//run('find {{deploy_path}}/releases/* -maxdepth 0 -type f | sort -r | tail -n +'.($nbArchiveTarGz+1).''); print_r($run->toString());
});


task('deploy:symlink_log', function () {
	run('ln -s {{deploy_path}}/FIC_TMP/LOGS {{release_path}}/LOGS');
	write('Creation du lien symbolique vers le repertoire de logs');
});


task('deploy:permission', function () {
	// Modification des proprietaires des fichiers de CRON PHP et SH
	//run('chown {{user_defaut}}:{{user_web}} {{release_path}}/Batch/*.php');
	//run('chown {{user_defaut}}:{{user_web}} {{release_path}}/Batch/*.sh');
	//run('chown -R {{user_defaut}}:{{user_web}} {{deploy_path}}/FIC_TMP');
	
	// Modification des permissions des repertoires TMP et LOGS
	run('chmod {{permission_batch_php}} {{release_path}}/Batch/*.php || true'); // " || true" <=> Ne retourne pas d'erreur en cas d'echec
	run('chmod {{permission_batch_sh}} {{release_path}}/Batch/*.sh || true');
	run('chmod -R {{permission_batch_bkp}} {{deploy_path}}/FIC_TMP || true');
});


task('deploy:done', function () {
    write('Deploiement effectue!');
});


task('deploy', [
    'deploy:prepare',
    'deploy:release',
    'deploy:update_code',
    'deploy:clear_controllers',
    'deploy:create_cache_dir',
    'deploy:shared',
    'deploy:assets',
    'deploy:symlink',
	'deploy:mv_config',
    'deploy:config_deploy',
    'deploy:symlink_phpmyadmin',
    'deploy:symlink_info_php',
    'deploy:archive',
    'deploy:delete_archive_gz',
    'deploy:symlink_log',
    'deploy:permission',
	'cleanup',
	'deploy:done'
]);






