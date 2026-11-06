<#
Misura il Livello 1 con la regola di controllo (docs/PERFORMANCE.md, sezione "Indagine").
Ogni giro usa server nuovi e misura health prima e dopo elenco e ricerca.
Il giro vale solo se i due health sono sotto $MaxControlMs, altrimenti si ripete.
Uso: .\benchmarks\measure.ps1 -ControlDir <cartella con ok.html e index.php, fuori dal repository>
#>
param(
    [Parameter(Mandatory)][string]$ControlDir,
    [string]$OutDir = 'results/level-01/rerun',
    [int]$Rounds = 6,
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

function Invoke-K6($name, $endpoint, $duration, $base, $pause) {
    $k6args = @('run', '-e', "ENDPOINT=$endpoint")
    if ($duration) { $k6args += '-e', "DURATION=$duration" }
    if ($base) { $k6args += '-e', "BASE_URL=$base" }
    if ($pause) { $k6args += '-e', "PAUSE=$pause" }
    $k6args += '--summary-export', "$OutDir/$name.json", 'smoke.js'
    k6 @k6args | Out-Null
    if (-not (Test-Path "$OutDir/$name.json")) { throw "k6 non ha scritto il risultato: $name" }
}

function Get-Median($name) {
    $m = (Get-Content "$OutDir/$name.json" -Raw | ConvertFrom-Json).metrics
    [math]::Round($m.http_req_duration.med, 1)
}

Push-Location $PSScriptRoot
try {
    foreach ($port in 8000, 8001) {
        if (Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue) {
            throw "La porta $port e' gia' in uso: chiudi il server prima di misurare."
        }
    }
    New-Item -ItemType Directory -Force $OutDir | Out-Null

    foreach ($n in 1..$Rounds) {
        $valid = $false
        foreach ($attempt in 1..$MaxAttempts) {
            $laravel = $null
            $control = $null
            try {
                $laravel = Start-Process php -ArgumentList 'artisan', 'serve', '--port=8000' -WorkingDirectory (Join-Path $root 'backend') -WindowStyle Hidden -PassThru
                $control = Start-Process php -ArgumentList '-S', '127.0.0.1:8001', '-t', "`"$ControlDir`"" -WindowStyle Hidden -PassThru
                Wait-Ready 'http://127.0.0.1:8000/api/v1/health'
                Wait-Ready 'http://127.0.0.1:8001/ok.html'

                Invoke-K6 "health-before-run$n" '/api/v1/health'
                Invoke-K6 "list-run$n" '/api/v1/ingredients' '60s'
                Invoke-K6 "search-run$n" '/api/v1/ingredients?search=farina' '60s'
                Invoke-K6 "health-after-run$n" '/api/v1/health'
                Invoke-K6 "ctrl-static-run$n" '/ok.html' '' 'http://127.0.0.1:8001' '0.1'
                Invoke-K6 "ctrl-php-run$n" '/index.php' '' 'http://127.0.0.1:8001' '0.1'
            }
            finally {
                Stop-Tree $laravel
                Stop-Tree $control
            }

            $before = Get-Median "health-before-run$n"
            $after = Get-Median "health-after-run$n"
            $list = Get-Median "list-run$n"
            $search = Get-Median "search-run$n"
            $valid = ($before -lt $MaxControlMs) -and ($after -lt $MaxControlMs)
            $esito = if ($valid) { 'valido' } else { 'SCARTATO' }
            "giro $n, tentativo ${attempt}: health prima $before ms, dopo $after ms, elenco $list ms, ricerca $search ms -> $esito"
            if ($valid) { break }
            Get-ChildItem "$OutDir/*-run$n.json" | Rename-Item -NewName { $_.BaseName + "-scartato$attempt.json" }
        }
        if (-not $valid) { throw "Il giro $n non ha un controllo valido dopo $MaxAttempts tentativi." }
    }
    'Fatto.'
}
finally {
    Pop-Location
}