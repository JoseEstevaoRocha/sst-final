@echo off
setlocal EnableDelayedExpansion

:: ── Detecta automaticamente o diretório do projeto ──────────────────────────
:: %~dp0 retorna o caminho completo da pasta onde este .bat está localizado
set "PROJETO=%~dp0"
set "PROJETO=%PROJETO:~0,-1%"

set "PORT=8000"
set "URL=http://127.0.0.1:8000"
set "PHP=php"

cd /d "%PROJETO%"

:: ─────────────────────────────────────────────────────────────────────────────
:: 1. Se o servidor já está rodando, apenas abre o navegador e sai
:: ─────────────────────────────────────────────────────────────────────────────
netstat -ano 2>nul | findstr /C:":%PORT% " | findstr "LISTENING" >nul 2>&1
if !errorlevel! == 0 (
    start "" "%URL%"
    exit /b 0
)

:: ─────────────────────────────────────────────────────────────────────────────
:: 2. Aguarda o PostgreSQL ficar disponível (máx 40 segundos)
:: ─────────────────────────────────────────────────────────────────────────────
set /a "pg_tentativas=0"
:aguarda_pg
    netstat -ano 2>nul | findstr /C:":5432 " | findstr "LISTENING" >nul 2>&1
    if !errorlevel! == 0 goto pg_pronto
    set /a "pg_tentativas+=1"
    if !pg_tentativas! geq 20 goto pg_timeout
    timeout /t 2 /nobreak >nul
    goto aguarda_pg

:pg_timeout
    :: PostgreSQL pode ser externo (ex: Supabase); continua mesmo assim
    goto otimiza

:pg_pronto
    timeout /t 1 /nobreak >nul

:: ─────────────────────────────────────────────────────────────────────────────
:: 3. Otimiza o Laravel (cache de config, rotas e views)
:: ─────────────────────────────────────────────────────────────────────────────
:otimiza
    %PHP% artisan config:cache  >nul 2>&1
    %PHP% artisan route:cache   >nul 2>&1
    %PHP% artisan view:cache    >nul 2>&1

:: ─────────────────────────────────────────────────────────────────────────────
:: 4. Inicia o servidor PHP completamente em segundo plano (sem janela)
:: ─────────────────────────────────────────────────────────────────────────────
wscript //nologo "%PROJETO%\php-server.vbs"

:: ─────────────────────────────────────────────────────────────────────────────
:: 5. Aguarda o servidor responder (máx 20 segundos)
:: ─────────────────────────────────────────────────────────────────────────────
set /a "srv_tentativas=0"
:aguarda_servidor
    timeout /t 1 /nobreak >nul
    netstat -ano 2>nul | findstr /C:":%PORT% " | findstr "LISTENING" >nul 2>&1
    if !errorlevel! == 0 goto servidor_pronto
    set /a "srv_tentativas+=1"
    if !srv_tentativas! geq 20 goto abre_mesmo_assim
    goto aguarda_servidor

:abre_mesmo_assim
:servidor_pronto
    start "" "%URL%"

:: ─────────────────────────────────────────────────────────────────────────────
:: 6. Inicia o agendador Laravel em segundo plano
::    Necessário para o sync automático do CAEPI e demais schedules
:: ─────────────────────────────────────────────────────────────────────────────
    wscript //nologo "%PROJETO%\schedule-worker.vbs"

:: ─────────────────────────────────────────────────────────────────────────────
:: 7. Sincroniza CAEPI em background (atualiza base de CAs na inicialização)
:: ─────────────────────────────────────────────────────────────────────────────
    wscript //nologo "%PROJETO%\caepi-sync.vbs"

    exit /b 0
