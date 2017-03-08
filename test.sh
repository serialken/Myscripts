#!/bin/bash
# URLS du dépot GIT
GIT_REPO_URL_HTTPS=https://gitlab.acensi.fr/ratp/cml.git
GIT_REPO_URL_SSH=git@gitlab.acensi.fr:ratp/cml.git

if [ "$#" == 0 ]; then
		# Récupération du numero du dernier commit
#		GIT_LAST_REV=$(git log |grep "commit" | head -1)
		GIT_LAST_TAG=$(git tag --sort=v:refname | tail -1 )
	else
		GIT_LAST_REV=$1
fi

echo "Récupération du dernier tag $GIT_LAST_TAG depuis $GIT_REPO_URL_SSH"