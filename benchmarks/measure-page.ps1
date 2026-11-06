<#
Misura la pagina dell'elenco con la regola di controllo (docs/PERFORMANCE.md, sezione "Indagine").
Avvia API e anteprima del frontend nuove, misura health prima e dopo benchmarks/page.mjs.
La serie vale solo se i due health sono sotto $MaxControlMs, altrimenti si ripete.
Prima serve `pnpm build` in frontend/. Uso: .\benchmarks\measure-page.ps1
#>
param(
    [string]$OutDir = 'results/level-01/rerun',
    [int]$MaxAttempts = 3,
    [double]$MaxControlMs = 150
)

$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent

function Stop-Tree($proc) {
    if ($proc -and -not $proc.HasExited) { taskkill /PID $proc.Id /T /F | Out-Null }
}

function Wait-Ready($url) {
    foreach ($i in 1..60) {
        if ((curl.exe -s -o NUL -w '%{http_code}' $url) -eq '200') { return }
        Start-Sleep -Milliseconds 500
    }
    throw "Il server non risponde: $url"
}

function Invoke-Health($name) {
    k6 run -e ENDPOINT=/api/v1/health --summary-export="$OutDir/$name.json" smoke.js | Out-Null
    if (-not (Test-Path "$OutDir/$name.json")) { throw "k6 non ha scritto il risultato: $name" }
    $m = (Get-Content "$OutDir/$name.json" -Raw | ConvertFrom-Json).metrics
    [math]::Round($m.http_req_duration.med, 1)
}

Push-Location $PSScriptRoot
try {
    foreach ($port in 8000, 4173) {
        if (Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue) {
            throw "La porta $port e' gia' in uso: chiudi il server prima di misurare."
        }
    }
    if (-not (Test-Path (Join-Path $root 'frontend/dist/index.html'))) {
        throw "Manca frontend/dist: esegui prima pnpm build nella cartella frontend."
    }
    New-Item -ItemType Directory -Force $OutDir | Out-Null

    $valid = $false
    foreach ($attempt in 1..$MaxAttempts) {
        $laravel = $null
        $preview = $null
        try {
            $laravel = Start-Process php -ArgumentList 'artisan', 'serve', '--port=8000' -WorkingDirectory (Join-Path $root 'backend') -WindowStyle Hidden -PassThru
            $preview = Start-Process pnpm -ArgumentList 'preview', '--host', '127.0.0.1', '--port', '4173', '--strictPort' -WorkingDirectory (Join-Path $root 'frontend') -WindowStyle Hidden -PassThru
            Wait-Ready 'http://127.0.0.1:8000/api/v1/health'
            Wait-Ready 'http://127.0.0.1:4173/'

            $before = Invoke-Health 'page-health-before'
            $env:OUT = "$OutDir/page.json"
            node page.mjs
            if ($LASTEXITCODE -ne 0) { throw 'page.mjs e fallito.' }
            $after = Invoke-Health 'page-health-after'
        }
        finally {
            Remove-Item Env:OUT -ErrorAction SilentlyContinue
            Stop-Tree $laravel
            Stop-Tree $preview
        }

        $valid = ($before -lt $MaxControlMs) -and ($after -lt $MaxControlMs)
        $esito = if ($valid) { 'valida' } else { 'SCARTATA' }
        "tentativo ${attempt}: health prima $before ms, dopo $after ms -> serie $esito"
        if ($valid) { break }
        foreach ($f in 'page', 'page-health-before', 'page-health-after') {
            Rename-Item "$OutDir/$f.json" "$f-scartato$attempt.json"
        }
    }
    if (-not $valid) { throw "Nessun controllo valido dopo $MaxAttempts tentativi." }
    'Fatto.'
}
finally {
    Pop-Location
}
