<#
.SYNOPSIS
    Crea .env.docker.app y .env.docker.db para la pila Docker, con secretos aleatorios.

.DESCRIPTION
    - Parte de las plantillas versionadas .env.docker.app.example y .env.docker.db.example.
    - Genera APP_KEY (32 bytes, igual que "php artisan key:generate") y una contraseña para la base
      (24 bytes en hexadecimal, 48 caracteres) con el generador criptográfico de .NET
      (System.Security.Cryptography.RandomNumberGenerator).
    - Pone la misma contraseña en DB_PASSWORD (app) y en MYSQL_PASSWORD (db).
    - Nunca sobrescribe: si alguno de los dos archivos ya existe, no cambia nada.
    - Nunca muestra los secretos en pantalla.

    Los dos archivos quedan fuera de Git (.gitignore). Guía completa: docs/docker.md.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File scripts\docker-init.ps1

    (-ExecutionPolicy Bypass permite correr este script solo esta vez, sin cambiar la
    configuración de Windows.)
#>

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

# La raíz del proyecto es la carpeta de arriba de scripts/
$raiz = Split-Path -Parent $PSScriptRoot
$rutaApp = Join-Path $raiz '.env.docker.app'
$rutaDb = Join-Path $raiz '.env.docker.db'

# --- 1. No sobrescribir nada -------------------------------------------------------------------
# Los dos archivos van juntos (comparten la contraseña): si ya hay uno, no se toca ninguno.
$existentes = @(@($rutaApp, $rutaDb) | Where-Object { Test-Path -LiteralPath $_ })
if ($existentes.Count -gt 0) {
    $nombres = ($existentes | ForEach-Object { Split-Path -Leaf $_ }) -join ' y '
    Write-Host "No se cambió nada: ya existe $nombres." -ForegroundColor Yellow
    Write-Host 'Para generarlos de nuevo, primero borra o renombra .env.docker.app y .env.docker.db.'
    Write-Host 'Ojo: si la base de Docker ya se creó, MySQL sigue con la contraseña anterior (docs/docker.md).'
    exit 1
}

# --- 2. Secretos aleatorios --------------------------------------------------------------------
function BytesAleatorios([int] $cantidad) {
    $bytes = New-Object byte[] $cantidad
    $generador = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    try { $generador.GetBytes($bytes) } finally { $generador.Dispose() }
    return , $bytes
}

$appKey = 'base64:' + [Convert]::ToBase64String((BytesAleatorios 32))
$claveBase = -join ((BytesAleatorios 24) | ForEach-Object { $_.ToString('x2') })

# --- 3. Completar las plantillas ---------------------------------------------------------------
function LeerPlantilla([string] $nombre) {
    $ruta = Join-Path $raiz $nombre
    if (-not (Test-Path -LiteralPath $ruta)) { throw "Falta la plantilla $nombre." }
    return [System.IO.File]::ReadAllText($ruta)
}

# Cada variable tiene que estar vacía exactamente una vez en la plantilla ("NOMBRE=")
function Completar([string] $texto, [string] $nombre, [string] $valor) {
    $patron = "(?m)^$nombre=\r?$"
    $cuantas = [regex]::Matches($texto, $patron).Count
    if ($cuantas -ne 1) { throw "La plantilla debería tener una sola línea '$nombre=' vacía (tiene $cuantas)." }
    return [regex]::Replace($texto, $patron, { param($coincidencia) "$nombre=$valor" })
}

function ValorDe([string] $texto, [string] $nombre) {
    $coincidencia = [regex]::Match($texto, "(?m)^$nombre=(.*?)\r?$")
    if (-not $coincidencia.Success) { throw "Falta $nombre en la plantilla." }
    return $coincidencia.Groups[1].Value
}

$textoApp = LeerPlantilla '.env.docker.app.example'
$textoDb = LeerPlantilla '.env.docker.db.example'

# La base y el usuario tienen que ser los mismos en los dos archivos
foreach ($par in @(@('DB_DATABASE', 'MYSQL_DATABASE'), @('DB_USERNAME', 'MYSQL_USER'))) {
    if ((ValorDe $textoApp $par[0]) -ne (ValorDe $textoDb $par[1])) {
        throw "$($par[0]) (.env.docker.app.example) y $($par[1]) (.env.docker.db.example) tienen que ser iguales."
    }
}

$textoApp = Completar $textoApp 'APP_KEY' $appKey
$textoApp = Completar $textoApp 'DB_PASSWORD' $claveBase
$textoDb = Completar $textoDb 'MYSQL_PASSWORD' $claveBase

# --- 4. Escribir sin sobrescribir --------------------------------------------------------------
# FileMode CreateNew falla si el archivo apareció mientras tanto. UTF-8 sin BOM (Docker lo lee así).
function EscribirNuevo([string] $ruta, [string] $texto) {
    $flujo = [System.IO.File]::Open($ruta, [System.IO.FileMode]::CreateNew, [System.IO.FileAccess]::Write, [System.IO.FileShare]::None)
    try {
        $escritor = New-Object System.IO.StreamWriter($flujo, (New-Object System.Text.UTF8Encoding($false)))
        $escritor.Write($texto)
        $escritor.Flush()
    } finally {
        $flujo.Dispose()
    }
}

EscribirNuevo $rutaApp $textoApp
try {
    EscribirNuevo $rutaDb $textoDb
} catch {
    # Sin el segundo archivo el primero no sirve (la contraseña no estaría en ningún otro lado)
    Remove-Item -LiteralPath $rutaApp
    throw
}

Write-Host 'Creado .env.docker.app (APP_KEY y DB_PASSWORD nuevos).' -ForegroundColor Green
Write-Host 'Creado .env.docker.db (MYSQL_PASSWORD, igual a DB_PASSWORD).' -ForegroundColor Green
Write-Host 'Los secretos no se muestran. Siguiente paso: docker compose up -d --build'
