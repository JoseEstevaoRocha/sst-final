@echo off
setlocal EnableDelayedExpansion

set "PROJETO=%~dp0"
set "PROJETO=%PROJETO:~0,-1%"
cd /d "%PROJETO%"

echo.
echo ======================================================
echo   SST Manager - Configuracao Inicial
echo ======================================================
echo.
echo  Escolha o modo de instalacao:
echo.
echo  [1] Instalacao nova
echo      Banco vazio + dados de demonstracao
echo      Use em: maquina nova sem dados proprios
echo.
echo  [2] Restaurar meu backup .sql
echo      Importa seus dados reais (sem dados demo)
echo      Use em: maquina nova com backup do banco real
echo.
echo  [3] Atualizar projeto existente
echo      Apenas instala novas dependencias e migrate
echo      Use em: projeto ja instalado, so puxou atualizacoes
echo.
set /p "MODO=Digite 1, 2 ou 3 e pressione ENTER: "

if "%MODO%"=="1" goto modo_novo
if "%MODO%"=="2" goto modo_backup
if "%MODO%"=="3" goto modo_update

echo [ERRO] Opcao invalida. Execute novamente e escolha 1, 2 ou 3.
pause
exit /b 1


:: =============================================================
:: SUBROTINAS COMPARTILHADAS
:: =============================================================

:verificar_pre_requisitos
echo.
echo -- Verificando pre-requisitos --
php -r "echo PHP_VERSION;" >nul 2>&1
if errorlevel 1 (
    echo [ERRO] PHP nao encontrado no PATH.
    echo        Instale o PHP 8.2+ e adicione ao PATH do sistema.
    echo        Sugestao: https://herd.laravel.com
    pause
    exit /b 1
)
for /f %%v in ('php -r "echo PHP_VERSION;"') do set "PHP_VER=%%v"
echo [OK] PHP %PHP_VER%

set "EXT_FALTANDO="
for %%e in (pdo pdo_pgsql curl zip mbstring openssl tokenizer xml) do (
    php -r "extension_loaded('%%e') or exit(1);" >nul 2>&1
    if errorlevel 1 set "EXT_FALTANDO=!EXT_FALTANDO! %%e"
)
if defined EXT_FALTANDO (
    echo [AVISO] Extensoes PHP nao habilitadas:!EXT_FALTANDO!
    echo         Habilite-as no php.ini - remova o ";" antes de "extension=..."
    echo         Pressione qualquer tecla para continuar mesmo assim...
    pause >nul
) else (
    echo [OK] Extensoes PHP OK
)

composer --version >nul 2>&1
if errorlevel 1 (
    echo [ERRO] Composer nao encontrado. Instale em: https://getcomposer.org
    pause
    exit /b 1
)
echo [OK] Composer OK
goto :eof


:instalar_deps
echo.
echo -- Instalando dependencias (composer install) --
composer install --no-interaction --prefer-dist --optimize-autoloader
if errorlevel 1 (
    echo [ERRO] Falha no composer install.
    pause
    exit /b 1
)
echo [OK] Dependencias instaladas
goto :eof


:configurar_env
echo.
echo -- Configurando .env --
if not exist "%PROJETO%\.env" (
    copy "%PROJETO%\.env.example" "%PROJETO%\.env" >nul
    echo [OK] .env criado a partir do .env.example
    echo.
    echo  Preencha o .env com os dados do seu banco:
    echo    DB_HOST     ex: 127.0.0.1
    echo    DB_DATABASE ex: sst_db
    echo    DB_USERNAME ex: postgres
    echo    DB_PASSWORD ex: sua_senha
    echo.
    echo  Abrindo .env no Bloco de Notas...
    start /wait notepad "%PROJETO%\.env"
    echo  Pressione ENTER para continuar apos salvar o .env.
    pause >nul
) else (
    echo [OK] .env ja existe, mantendo configuracoes atuais
)
goto :eof


:gerar_key
echo.
echo -- Gerando chave da aplicacao --
php artisan key:generate --force
if errorlevel 1 (
    echo [ERRO] Falha ao gerar chave. Verifique o .env.
    pause
    exit /b 1
)
echo [OK] APP_KEY gerada
goto :eof


:storage_link
echo.
echo -- Criando link de storage --
php artisan storage:link >nul 2>&1
echo [OK] Link de storage configurado
goto :eof


:limpar_cache
echo.
echo -- Limpando caches --
php artisan config:clear >nul 2>&1
php artisan cache:clear  >nul 2>&1
php artisan view:clear   >nul 2>&1
echo [OK] Caches limpos
goto :eof


:ler_env
for /f "usebackq tokens=1,* delims==" %%a in ("%PROJETO%\.env") do (
    if "%%a"=="DB_HOST"     set "DB_H=%%b"
    if "%%a"=="DB_PORT"     set "DB_P=%%b"
    if "%%a"=="DB_DATABASE" set "DB_D=%%b"
    if "%%a"=="DB_USERNAME" set "DB_U=%%b"
    if "%%a"=="DB_PASSWORD" set "DB_PASS=%%b"
)
for %%v in (DB_H DB_P DB_D DB_U DB_PASS) do (
    if defined %%v (
        set "%%v=!%%v: =!"
        set "%%v=!%%v:"=!"
    )
)
if not defined DB_H    set "DB_H=127.0.0.1"
if not defined DB_P    set "DB_P=5432"
if not defined DB_U    set "DB_U=postgres"
if not defined DB_PASS set "DB_PASS="
goto :eof


:: =============================================================
:: MODO 1 - Instalacao nova com dados demo
:: =============================================================
:modo_novo
echo.
echo ======================================================
echo   MODO 1: Instalacao nova com dados de demonstracao
echo ======================================================

call :verificar_pre_requisitos
if errorlevel 1 exit /b 1

call :instalar_deps
if errorlevel 1 exit /b 1

call :configurar_env

call :gerar_key
if errorlevel 1 exit /b 1

echo.
echo -- Criando tabelas no banco --
php artisan migrate --force
if errorlevel 1 (
    echo [ERRO] Falha nas migrations.
    echo        Verifique se o PostgreSQL esta rodando e os dados do .env estao corretos.
    pause
    exit /b 1
)
echo [OK] Tabelas criadas

echo.
echo -- Inserindo dados de demonstracao --
php artisan db:seed --force
if errorlevel 1 (
    echo [AVISO] Seed falhou. Continuando...
) else (
    echo [OK] Dados de demonstracao inseridos
    echo      Login: admin@sst.com / password
)

call :storage_link
call :limpar_cache

echo.
echo ======================================================
echo   Configuracao concluida com sucesso!
echo ======================================================
echo.
echo   Para iniciar: clique em start-sst.vbs
echo   Acesso:       http://localhost:8000
echo   Login demo:   admin@sst.com / password
echo.
pause
exit /b 0


:: =============================================================
:: MODO 2 - Restaurar backup SQL (dados reais)
:: =============================================================
:modo_backup
echo.
echo ======================================================
echo   MODO 2: Restaurar backup SQL (seus dados reais)
echo ======================================================
echo.
echo  O backup deve ser um arquivo .sql gerado pelo pg_dump
echo  ou exportado pelo pgAdmin (formato Plain SQL).
echo.
echo  Cole o caminho completo do arquivo .sql:
echo  Exemplo: C:\Users\Voce\Downloads\backup_sst.sql
echo.
set /p "SQL_FILE=Caminho do arquivo .sql: "

set "SQL_FILE=%SQL_FILE:"=%"

if not exist "%SQL_FILE%" (
    echo.
    echo [ERRO] Arquivo nao encontrado: %SQL_FILE%
    echo        Verifique o caminho e execute o setup novamente.
    pause
    exit /b 1
)
echo [OK] Arquivo encontrado: %SQL_FILE%

call :verificar_pre_requisitos
if errorlevel 1 exit /b 1

call :instalar_deps
if errorlevel 1 exit /b 1

call :configurar_env

call :gerar_key
if errorlevel 1 exit /b 1

echo.
echo -- Aplicando migrations (sem apagar dados existentes) --
php artisan migrate --force
if errorlevel 1 (
    echo [ERRO] Falha nas migrations. Verifique a conexao com o banco.
    pause
    exit /b 1
)
echo [OK] Estrutura de tabelas atualizada

call :ler_env

echo.
echo -- Verificando se psql esta disponivel --
psql --version >nul 2>&1
if errorlevel 1 (
    echo [ERRO] psql nao encontrado no PATH.
    echo.
    echo  Adicione a pasta bin do PostgreSQL ao PATH do sistema.
    echo  Caminho comum: C:\Program Files\PostgreSQL\16\bin
    echo.
    echo  ALTERNATIVA - Importe manualmente pelo pgAdmin:
    echo    1. Abra o pgAdmin
    echo    2. Clique direito no banco %DB_D%
    echo    3. Restore... e selecione o arquivo: %SQL_FILE%
    echo.
    pause
    exit /b 1
)
echo [OK] psql disponivel

echo.
echo -- Importando backup para o banco --
echo    Banco:   %DB_D% em %DB_H%:%DB_P%
echo    Arquivo: %SQL_FILE%
echo.

set "PGPASSWORD=%DB_PASS%"
psql -h %DB_H% -p %DB_P% -U %DB_U% -d %DB_D% -f "%SQL_FILE%"
set "PGPASSWORD="

if errorlevel 1 (
    echo.
    echo [ERRO] Falha ao importar o backup.
    echo        Possiveis causas:
    echo          - Senha incorreta no .env
    echo          - Banco "%DB_D%" nao existe (crie no pgAdmin primeiro)
    echo          - Arquivo .sql incompativel com esta versao do PostgreSQL
    pause
    exit /b 1
)
echo [OK] Backup importado com sucesso!

call :storage_link
call :limpar_cache

echo.
echo ======================================================
echo   Configuracao concluida com sucesso!
echo ======================================================
echo.
echo   Todos os seus dados foram restaurados.
echo   Para iniciar: clique em start-sst.vbs
echo   Acesso:       http://localhost:8000
echo.
pause
exit /b 0


:: =============================================================
:: MODO 3 - Atualizar projeto ja instalado
:: =============================================================
:modo_update
echo.
echo ======================================================
echo   MODO 3: Atualizar projeto (preserva todos os dados)
echo ======================================================

call :verificar_pre_requisitos
if errorlevel 1 exit /b 1

echo.
echo -- Atualizando dependencias --
composer install --no-interaction --prefer-dist --optimize-autoloader
if errorlevel 1 (
    echo [ERRO] Falha no composer install.
    pause
    exit /b 1
)
echo [OK] Dependencias atualizadas

echo.
echo -- Aplicando novas migrations (sem apagar dados) --
php artisan migrate --force
if errorlevel 1 (
    echo [ERRO] Falha nas migrations. Verifique a conexao com o banco.
    pause
    exit /b 1
)
echo [OK] Banco de dados atualizado

call :storage_link
call :limpar_cache

echo.
echo ======================================================
echo   Projeto atualizado com sucesso!
echo ======================================================
echo.
echo   Seus dados foram preservados.
echo   Para iniciar: clique em start-sst.vbs
echo   Acesso:       http://localhost:8000
echo.
pause
exit /b 0
