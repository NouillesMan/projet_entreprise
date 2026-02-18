<#
.SYNOPSIS
    Collecte les informations d'un PC Windows et les sauvegarde en CSV.
.DESCRIPTION
    Script de collecte d'inventaire PC pour cle USB.
    Recupere hostname, serial, marque, modele, OS, version OS, architecture, domaine.
    Ajoute une ligne dans inventaire.csv (meme dossier que le script).
.NOTES
    Executer en tant qu'administrateur pour recuperer le numero de serie.
#>

$ErrorActionPreference = "Stop"

# Dossier du script (= racine de la cle USB)
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Definition
$csvPath   = Join-Path $scriptDir "inventaire.csv"

# --- Collecte des informations ---
$cs   = Get-CimInstance Win32_ComputerSystem
$bios = Get-CimInstance Win32_BIOS
$os   = Get-CimInstance Win32_OperatingSystem

$hostname     = $env:COMPUTERNAME
$serial       = $bios.SerialNumber
$marque       = $cs.Manufacturer
$modele       = $cs.Model
$utilisateur  = "$($cs.Domain)\$($cs.UserName)" -replace "^\\", ""
$osName       = $os.Caption
$osVersion    = $os.Version
$architecture = if ([Environment]::Is64BitOperatingSystem) { "x64" } else { "x86" }
$domaine      = $cs.Domain
$statut       = "En service"

# Nettoyage (supprimer les virgules pour eviter de casser le CSV)
$marque  = $marque  -replace ",", " "
$modele  = $modele  -replace ",", " "
$osName  = $osName  -replace ",", " "

# --- Construction de la ligne CSV ---
$header = "hostname,serial,marque,modele,utilisateur,os,os_version,architecture,domaine,statut"
$line   = "$hostname,$serial,$marque,$modele,$utilisateur,$osName,$osVersion,$architecture,$domaine,$statut"

# --- Ecriture dans le fichier CSV ---
if (-Not (Test-Path $csvPath)) {
    # Creer le fichier avec le header
    $header | Out-File -FilePath $csvPath -Encoding UTF8
}

# Ajouter la ligne de donnees
$line | Out-File -FilePath $csvPath -Encoding UTF8 -Append

# --- Affichage du resume ---
Write-Host ""
Write-Host "=== Collecte terminee ===" -ForegroundColor Green
Write-Host "Hostname      : $hostname"
Write-Host "Serial        : $serial"
Write-Host "Marque        : $marque"
Write-Host "Modele        : $modele"
Write-Host "Utilisateur   : $utilisateur"
Write-Host "OS            : $osName"
Write-Host "Version OS    : $osVersion"
Write-Host "Architecture  : $architecture"
Write-Host "Domaine       : $domaine"
Write-Host "Statut        : $statut"
Write-Host ""
Write-Host "Fichier CSV   : $csvPath" -ForegroundColor Cyan
Write-Host ""
