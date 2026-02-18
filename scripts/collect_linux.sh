#!/usr/bin/env bash
#
# Collecte les informations d'un PC Linux et les sauvegarde en CSV.
# Necessite sudo pour dmidecode (serial, marque, modele).
#
# Usage : sudo bash collect_linux.sh
#

set -euo pipefail

# Dossier du script (= racine de la cle USB)
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
CSV_PATH="$SCRIPT_DIR/inventaire.csv"

# --- Collecte des informations ---
HOSTNAME_VAL=$(hostname)
SERIAL=$(sudo dmidecode -s system-serial-number 2>/dev/null || echo "N/A")
MARQUE=$(sudo dmidecode -s system-manufacturer 2>/dev/null || echo "N/A")
MODELE=$(sudo dmidecode -s system-product-name 2>/dev/null || echo "N/A")
UTILISATEUR=$(whoami)

# OS et version
if command -v lsb_release &>/dev/null; then
    OS_NAME=$(lsb_release -d -s 2>/dev/null || echo "Linux")
    OS_VERSION=$(lsb_release -r -s 2>/dev/null || echo "")
elif [ -f /etc/os-release ]; then
    OS_NAME=$(. /etc/os-release && echo "$NAME")
    OS_VERSION=$(. /etc/os-release && echo "$VERSION_ID")
else
    OS_NAME="Linux"
    OS_VERSION=$(uname -r)
fi

# Architecture
ARCH=$(uname -m)
case "$ARCH" in
    x86_64)  ARCHITECTURE="x64" ;;
    i686|i386) ARCHITECTURE="x86" ;;
    aarch64) ARCHITECTURE="arm64" ;;
    *)       ARCHITECTURE="x64" ;;
esac

# Domaine
DOMAINE=$(hostname -d 2>/dev/null || echo "")

STATUT="En service"

# Nettoyage (supprimer les virgules)
SERIAL="${SERIAL//,/ }"
MARQUE="${MARQUE//,/ }"
MODELE="${MODELE//,/ }"
OS_NAME="${OS_NAME//,/ }"

# --- Ecriture dans le fichier CSV ---
HEADER="hostname,serial,marque,modele,utilisateur,os,os_version,architecture,domaine,statut"

if [ ! -f "$CSV_PATH" ]; then
    echo "$HEADER" > "$CSV_PATH"
fi

echo "$HOSTNAME_VAL,$SERIAL,$MARQUE,$MODELE,$UTILISATEUR,$OS_NAME,$OS_VERSION,$ARCHITECTURE,$DOMAINE,$STATUT" >> "$CSV_PATH"

# --- Affichage du resume ---
echo ""
echo "=== Collecte terminee ==="
echo "Hostname      : $HOSTNAME_VAL"
echo "Serial        : $SERIAL"
echo "Marque        : $MARQUE"
echo "Modele        : $MODELE"
echo "Utilisateur   : $UTILISATEUR"
echo "OS            : $OS_NAME"
echo "Version OS    : $OS_VERSION"
echo "Architecture  : $ARCHITECTURE"
echo "Domaine       : $DOMAINE"
echo "Statut        : $STATUT"
echo ""
echo "Fichier CSV   : $CSV_PATH"
echo ""
