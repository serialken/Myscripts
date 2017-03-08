#!/usr/bin/env bash
#Script pour afficher une matrix sur le terminal
# Black - Regular '\e[0;30m'
# Red txtred='\e[0;31m'
# Green txtgrn='\e[0;32m'
# Yellow txtylw='\e[0;33m'
# Blue txtblu='\e[0;34m'
# Purple txtpur='\e[0;35m'
# Cyan txtcyn='\e[0;36m'
# White txtwht='\e[0;37m'
# Black - Bold bldblk='\e[1;30m'
# Red bldred='\e[1;31m'
# Green bldgrn='\e[1;32m'
# Yellow bldylw='\e[1;33m'
# Blue bldblu='\e[1;34m'
# Purple bldpur='\e[1;35m'
# Cyan bldcyn='\e[1;36m'
# White bldwht='\e[1;37m'
# Black - Underline unkblk='\e[4;30m'
# Red undred='\e[4;31m'
# Green undgrn='\e[4;32m'
undylw='\e[4;33m' # Yellow
undblu='\e[4;34m' # Blue
undpur='\e[4;35m' # Purple
undcyn='\e[4;36m' # Cyan
undwht='\e[4;37m' # White
bakblk='\e[40m'   # Black - Background
bakred='\e[41m'   # Red
bakgrn='\e[42m'   # Green
bakylw='\e[43m'   # Yellow
bakblu='\e[44m'   # Blue
bakpur='\e[45m'   # Purple
bakcyn='\e[46m'   # Cyan
bakwht='\e[47m'   # White
#$((RANDOM%8))
while true;
    do
        base="&#0";
         nbr=$((RANDOM%2));
          res="$base""$nbr" printf "\e[0;32m%x\e[0m" $nbr ;
        for ((i=0; i<$((RANDOM%128)); i++))
            do printf " ";
    done;
done;