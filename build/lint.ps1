# Esquema Rico - Lint de sintaxe PHP (Windows / PowerShell)
#
# Roda `php -l` em todos os arquivos .php de src/.
# Requer PHP no PATH (ou ajuste $php abaixo para o caminho do php.exe).
#
# Uso:  pwsh build/lint.ps1   (ou)   powershell -File build/lint.ps1

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$src  = Join-Path $root 'src'

$php = (Get-Command php -ErrorAction SilentlyContinue)?.Source
if (-not $php) {
    foreach ($c in @('C:\xampp\php\php.exe', 'C:\laragon\bin\php\php-*\php.exe', 'C:\wamp64\bin\php\php*\php.exe')) {
        $g = Get-ChildItem $c -ErrorAction SilentlyContinue | Select-Object -First 1
        if ($g) { $php = $g.FullName; break }
    }
}
if (-not $php) { Write-Error 'PHP não encontrado. Instale o PHP 8.3 ou ajuste o caminho.'; exit 1 }

Write-Host "Usando PHP: $php"
$files = Get-ChildItem -Path $src -Recurse -Filter *.php
$errors = 0

foreach ($f in $files) {
    $out = & $php -l $f.FullName 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Host "ERRO: $($f.FullName)" -ForegroundColor Red
        Write-Host $out
        $errors++
    }
}

Write-Host ""
if ($errors -eq 0) {
    Write-Host "OK: $($files.Count) arquivos sem erros de sintaxe." -ForegroundColor Green
} else {
    Write-Host "$errors arquivo(s) com erro de sintaxe." -ForegroundColor Red
    exit 1
}
