#!/bin/bash
# ─────────────────────────────────────────────────────────────
#  Inventaire PC — Script d'installation automatique
# ─────────────────────────────────────────────────────────────

set -e

BOLD="\033[1m"
GREEN="\033[0;32m"
YELLOW="\033[0;33m"
RED="\033[0;31m"
RESET="\033[0m"

ok()   { echo -e "${GREEN}✔ $1${RESET}"; }
info() { echo -e "${YELLOW}▶ $1${RESET}"; }
fail() { echo -e "${RED}✘ $1${RESET}"; exit 1; }

echo ""
echo -e "${BOLD}╔══════════════════════════════════════╗${RESET}"
echo -e "${BOLD}║   Inventaire PC — Setup              ║${RESET}"
echo -e "${BOLD}╚══════════════════════════════════════╝${RESET}"
echo ""

# ── 1. Check Docker ───────────────────────────────────────────
info "Vérification de Docker..."
if ! docker info > /dev/null 2>&1; then
  fail "Docker n'est pas démarré. Lancez Docker et réessayez."
fi
ok "Docker est disponible"

# ── 2. Start containers ───────────────────────────────────────
info "Démarrage des conteneurs Docker..."
docker compose up -d
ok "Conteneurs démarrés"

# ── 3. Wait for DB to be ready ────────────────────────────────
info "Attente que la base de données soit prête..."
RETRIES=30
until docker compose exec -T db mariadb -u root -proot inventaire_pc -e "SELECT 1" > /dev/null 2>&1; do
  RETRIES=$((RETRIES - 1))
  if [ $RETRIES -eq 0 ]; then
    fail "La base de données ne répond pas après 30 tentatives."
  fi
  echo -n "."
  sleep 2
done
echo ""
ok "Base de données prête"

# ── 4. Import schemas ─────────────────────────────────────────
info "Import des schémas SQL..."

for SCHEMA in schema_custom_fields.sql schema_options.sql schema_users.sql; do
  if [ -f "$SCHEMA" ]; then
    docker compose exec -T db mariadb -u root -proot inventaire_pc < "$SCHEMA"
    ok "  $SCHEMA importé"
  else
    echo -e "${YELLOW}  ⚠ $SCHEMA introuvable, ignoré${RESET}"
  fi
done

# ── 5. Done ───────────────────────────────────────────────────
echo ""
echo -e "${BOLD}${GREEN}══════════════════════════════════════${RESET}"
echo -e "${BOLD}${GREEN}  Installation terminée !             ${RESET}"
echo -e "${BOLD}${GREEN}══════════════════════════════════════${RESET}"
echo ""
echo -e "  URL       : ${BOLD}http://localhost:8080/login.php${RESET}"
echo -e "  Utilisateur: ${BOLD}admin${RESET}"
echo -e "  Mot de passe: ${BOLD}root${RESET}"
echo ""
echo -e "${YELLOW}  Pensez à changer le mot de passe après la première connexion.${RESET}"
echo ""
