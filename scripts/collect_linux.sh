#!/usr/bin/env bash
#
# Collecte les informations d'un PC Linux et les sauvegarde en CSV.
# Necessite sudo pour dmidecode (serial, marque, modele).
#
# Usage : sudo bash ./collect_linux.sh
# Note  : sur cle USB (FAT32), utiliser "sudo bash ./collect_linux.sh"
#         car le systeme de fichiers ne supporte pas le bit executable.
#

set -euo pipefail

# Dossier du script (= racine de la cle USB)
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

DATA_DIR="$SCRIPT_DIR/data"
LOGS_DIR="$SCRIPT_DIR/logs"
mkdir -p "$DATA_DIR" "$LOGS_DIR"

CSV_PATH="$DATA_DIR/inventaire.csv"
LOG_PATH="$LOGS_DIR/collect_log.txt"

# --- Collecte des informations ---
HOSTNAME_VAL=$(hostname)
SERIAL=$(sudo dmidecode -s system-serial-number 2>/dev/null || echo "N/A")
MARQUE=$(sudo dmidecode -s system-manufacturer 2>/dev/null || echo "N/A")
MODELE=$(sudo dmidecode -s system-product-name 2>/dev/null || echo "N/A")
if [ -n "${SUDO_USER:-}" ]; then
    UTILISATEUR="$SUDO_USER"
else
    UTILISATEUR=$(whoami)
fi

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
[ "$DOMAINE" = "(none)" ] && DOMAINE=""

STATUT="En service"

# Nettoyage (supprimer les virgules)
SERIAL="${SERIAL//,/ }"
MARQUE="${MARQUE//,/ }"
MODELE="${MODELE//,/ }"
OS_NAME="${OS_NAME//,/ }"

# --- Ecriture dans le fichier CSV ---
HEADER="hostname,serial,marque,modele,utilisateur,os,os_version,architecture,domaine,statut"
NEW_LINE="$HOSTNAME_VAL,$SERIAL,$MARQUE,$MODELE,$UTILISATEUR,$OS_NAME,$OS_VERSION,$ARCHITECTURE,$DOMAINE,$STATUT"
WAS_UPDATED=0

if [ -f "$CSV_PATH" ]; then
    # Verifier si le hostname ou le serial existe deja (hors header)
    if grep -q "^${HOSTNAME_VAL}," "$CSV_PATH" 2>/dev/null || \
       grep -q "^[^,]*,${SERIAL}," "$CSV_PATH" 2>/dev/null; then
        WAS_UPDATED=1
    fi
    # Remplacer la ligne existante ou ajouter en fin de fichier
    TMPFILE="${CSV_PATH}.tmp"
    awk -v host="$HOSTNAME_VAL" -v serial="$SERIAL" -v newline="$NEW_LINE" \
        'BEGIN{FS=",";found=0}
         NR==1{print;next}
         $1==host || $2==serial{print newline;found=1;next}
         {print}
         END{if(!found)print newline}' \
        "$CSV_PATH" > "$TMPFILE"
    mv "$TMPFILE" "$CSV_PATH"
else
    # Nouveau fichier CSV
    printf '%s\n' "$HEADER"   > "$CSV_PATH"
    printf '%s\n' "$NEW_LINE" >> "$CSV_PATH"
fi

# --- Ecriture du log ---
TS=$(date '+%Y-%m-%d %H:%M:%S')
{
    echo ""
    echo "============================================================"
    echo "[$TS] [INFO] Hostname     : $HOSTNAME_VAL"
    echo "[$TS] [INFO] Serial       : $SERIAL"
    echo "[$TS] [INFO] Utilisateur  : $UTILISATEUR"
    echo "[$TS] [INFO] OS           : $OS_NAME $OS_VERSION"
    echo "[$TS] [INFO] Architecture : $ARCHITECTURE"
    if [ "$WAS_UPDATED" -eq 1 ]; then
        echo "[$TS] [OK]   Action       : Mis a jour"
    else
        echo "[$TS] [OK]   Action       : Ajoute"
    fi
} >> "$LOG_PATH"

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
echo "Fichier log   : $LOG_PATH"
if [ "$WAS_UPDATED" -eq 1 ]; then
    echo "Action        : Mis a jour"
else
    echo "Action        : Ajoute"
fi
echo ""
