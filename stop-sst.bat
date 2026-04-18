@echo off
:: Para o servidor SST Manager e limpa os caches do Laravel

:: ── Detecta automaticamente o diretório do projeto ──────────────────────────
set "PROJETO=%~dp0"
set "PROJETO=%PROJETO:~0,-1%"

set "PORT=8000"

:: Mata o processo que está ouvindo na porta 8000
for /f "tokens=5" %%a in ('netstat -ano 2^>nul ^| findstr /C:":%PORT% " ^| findstr "LISTENING"') do (
    taskkill /PID %%a /F >nul 2>&1
)

:: Limpa os caches do Laravel
cd /d "%PROJETO%"
php artisan config:clear  >nul 2>&1
php artisan route:clear   >nul 2>&1
php artisan view:clear    >nul 2>&1

echo SST Manager parado.
timeout /t 2 /nobreak >nul
exit /b 0
