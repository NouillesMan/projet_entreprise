<#
.SYNOPSIS
    Collecte les informations d'un PC Windows et les sauvegarde en CSV.
.DESCRIPTION
    Script de collecte d'inventaire PC pour cle USB.
    Recupere hostname, serial, marque, modele, OS, version OS, architecture, domaine.
    Met a jour la ligne existante (hostname ou serial) ou ajoute une nouvelle entree.
    Compatible Windows 7 / PowerShell 2.0 et superieur.
.NOTES
    Utiliser lancer_collecte.bat pour executer ce script.
    Les logs sont enregistres dans collect_log.txt (meme dossier que le script).
#>

$ErrorActionPreference = "Stop"
$script:failed = $false

# Le PS1 est dans core\ — le dossier de sortie est le dossier parent (ou core\ en fallback)
$coreDir   = if ($PSScriptRoot) { $PSScriptRoot } else { Split-Path -Parent $MyInvocation.MyCommand.Definition }
$parentDir = Split-Path -Parent $coreDir
$scriptDir = if ($parentDir) { $parentDir } else { $coreDir }

$dataDir   = Join-Path $scriptDir "data"
$logsDir   = Join-Path $scriptDir "logs"
if (-not (Test-Path $dataDir)) { New-Item -ItemType Directory -Path $dataDir | Out-Null }
if (-not (Test-Path $logsDir)) { New-Item -ItemType Directory -Path $logsDir | Out-Null }

$csvPath   = Join-Path $dataDir "inventaire.csv"
$logPath   = Join-Path $logsDir "collect_log.txt"

# Affichage immediat des chemins pour debug (visible meme si ecriture disque impossible)
Write-Host "=== Collecte inventaire PC ===" -ForegroundColor Cyan
Write-Host "Script dir : $scriptDir"
Write-Host "CSV        : $csvPath"
Write-Host "Log        : $logPath"
Write-Host ""

# --- Compatibilite CIM / WMI (PS 3.0+ = CIM, PS 2.0 = WMI) ---
function Get-HWInfo {
    param([string]$Class)
    if (Get-Command Get-CimInstance -ErrorAction SilentlyContinue) {
        Get-CimInstance -ClassName $Class
    } else {
        Get-WmiObject -Class $Class
    }
}

# --- Logging ---
function Write-Log {
    param(
        [string]$Level,
        [string]$Message
    )
    $ts   = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $line = "[$ts] [$Level] $Message"
    try { Add-Content -Path $logPath -Value $line -Encoding UTF8 } catch {
        Write-Host "[AVERTISSEMENT] Ecriture log impossible : $_" -ForegroundColor Yellow
    }
    switch ($Level) {
        "OK"    { Write-Host $line -ForegroundColor Green }
        "ERROR" { Write-Host $line -ForegroundColor Red   }
        default { Write-Host $line                        }
    }
}

try {
    # Init log
    try { Add-Content -Path $logPath -Value "" -Encoding UTF8 } catch {
        Write-Host "[AVERTISSEMENT] Impossible d'ecrire le log : $_" -ForegroundColor Yellow
    }
    try { Add-Content -Path $logPath -Value ("=" * 60) -Encoding UTF8 } catch {
        Write-Host "[AVERTISSEMENT] Ecriture log impossible : $_" -ForegroundColor Yellow
    }

    Write-Log "INFO" "=== Debut de la collecte ==="
    Write-Log "INFO" "PowerShell version : $($PSVersionTable.PSVersion)"
    Write-Log "INFO" "Utilisateur OS     : $env:USERNAME"
    Write-Log "INFO" "Script dir         : $scriptDir"

    # --- Collecte des informations ---
    Write-Log "INFO" "Collecte Win32_ComputerSystem..."
    $cs = Get-HWInfo Win32_ComputerSystem
    Write-Log "OK"   "Win32_ComputerSystem OK"

    Write-Log "INFO" "Collecte Win32_BIOS..."
    $bios = Get-HWInfo Win32_BIOS
    Write-Log "OK"   "Win32_BIOS OK"

    Write-Log "INFO" "Collecte Win32_OperatingSystem..."
    $os = Get-HWInfo Win32_OperatingSystem
    Write-Log "OK"   "Win32_OperatingSystem OK"

    Write-Log "INFO" "Collecte Win32_Processor..."
    $proc = Get-HWInfo Win32_Processor | Select-Object -First 1
    Write-Log "OK"   "Win32_Processor OK"

    $hostname     = $env:COMPUTERNAME
    $serial       = $bios.SerialNumber
    $marque       = $cs.Manufacturer
    $modele       = $cs.Model
    $utilisateur  = if ($cs.UserName) { $cs.UserName -replace '^.*\\', '' } else { "N/A" }
    $osName       = $os.Caption
    $osVersion    = $os.Version
    $architecture = switch ($proc.Architecture) {
        0       { "x86" }
        9       { "x64" }
        12      { "arm64" }
        default { "x64" }
    }
    $domaine      = $cs.Domain
    $statut       = "En service"

    Write-Log "INFO" "Hostname     : $hostname"
    Write-Log "INFO" "Serial       : $serial"
    Write-Log "INFO" "Marque       : $marque"
    Write-Log "INFO" "Modele       : $modele"
    Write-Log "INFO" "Utilisateur  : $utilisateur"
    Write-Log "INFO" "OS           : $osName"
    Write-Log "INFO" "Version OS   : $osVersion"
    Write-Log "INFO" "Architecture : $architecture"
    Write-Log "INFO" "Domaine      : $domaine"

    # Nettoyage (supprimer les virgules pour eviter de casser le CSV)
    $serial      = $serial      -replace ",", " "
    $marque      = $marque      -replace ",", " "
    $modele      = $modele      -replace ",", " "
    $osName      = $osName      -replace ",", " "
    $utilisateur = $utilisateur -replace ",", " "

    # --- Construction de la ligne CSV ---
    $header = "hostname,serial,marque,modele,utilisateur,os,os_version,architecture,domaine,statut"
    $line   = "$hostname,$serial,$marque,$modele,$utilisateur,$osName,$osVersion,$architecture,$domaine,$statut"

    # --- Ecriture dans le fichier CSV ---
    $wasUpdated = $false

    if (Test-Path $csvPath) {
        Write-Log "INFO" "Fichier CSV existant detecte, verification doublon..."
        $content    = [System.IO.File]::ReadAllLines($csvPath, (New-Object System.Text.UTF8Encoding $false))
        $newContent = New-Object 'System.Collections.Generic.List[string]'
        $newContent.Add($content[0])

        for ($i = 1; $i -lt $content.Length; $i++) {
            if ($content[$i].Trim() -eq "") { continue }
            $fields = $content[$i] -split ","
            if ($fields[0] -eq $hostname -or $fields[1] -eq $serial) {
                $newContent.Add($line)
                $wasUpdated = $true
                Write-Log "INFO" "Ligne existante trouvee (ligne $($i+1)), mise a jour..."
            } else {
                $newContent.Add($content[$i])
            }
        }
        if (-not $wasUpdated) { $newContent.Add($line) }

        [System.IO.File]::WriteAllText($csvPath, ($newContent -join "`n") + "`n", (New-Object System.Text.UTF8Encoding $false))
    } else {
        Write-Log "INFO" "Nouveau fichier CSV, creation..."
        [System.IO.File]::WriteAllText($csvPath, "$header`n$line`n", (New-Object System.Text.UTF8Encoding $false))
    }

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
    Write-Host "Fichier log   : $logPath" -ForegroundColor Cyan
    if ($wasUpdated) {
        Write-Host "Action        : Mis a jour" -ForegroundColor Yellow
        Write-Log "OK" "Action : Mis a jour"
    } else {
        Write-Host "Action        : Ajoute"     -ForegroundColor Green
        Write-Log "OK" "Action : Ajoute"
    }
    Write-Log "OK" "=== Collecte terminee avec succes ==="

} catch {
    $script:failed = $true
    $errMsg  = $_.Exception.Message
    $errLine = $_.InvocationInfo.ScriptLineNumber
    $errType = $_.Exception.GetType().FullName
    try { Write-Log "ERROR" "ECHEC : $errMsg" }      catch {}
    try { Write-Log "ERROR" "Ligne  : $errLine" }    catch {}
    try { Write-Log "ERROR" "Detail : $errType" }    catch {}
    Write-Host ""
    Write-Host "ERREUR (ligne $errLine) : $errMsg" -ForegroundColor Red
    Write-Host "Type : $errType"                   -ForegroundColor Red
    Write-Host ""
    Write-Host "Si le log n'a pas ete cree, le chemin suivant est peut-etre incorrect :" -ForegroundColor Yellow
    Write-Host "  $scriptDir" -ForegroundColor Yellow
} finally {
    Write-Host ""
    Read-Host "Appuyer sur Entree pour fermer"
}

if ($script:failed) { exit 1 }
