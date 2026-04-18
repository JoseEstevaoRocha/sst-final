# 🔐 SST Manager v2.0 — Guia Completo de Deploy no VPS

## ✅ Stack
```
VPS:          Ubuntu 22.04 LTS
Web Server:   Nginx
PHP:          8.2 FPM
Banco:        PostgreSQL 16
Framework:    Laravel 11
SSL:          Let's Encrypt (Certbot)
Firewall:     UFW + Fail2Ban
```

---

## 📋 PARTE 1 — SEGURANÇA DO SERVIDOR LINUX

### 1.1 Primeiro acesso e atualização
```bash
# Atualize tudo antes de qualquer coisa
apt update && apt upgrade -y
apt install -y curl wget git unzip software-properties-common

# Ativar atualizações automáticas de segurança
apt install -y unattended-upgrades
dpkg-reconfigure --priority=low unattended-upgrades
```

### 1.2 Criar usuário não-root
```bash
adduser deployer
usermod -aG sudo deployer

# Copiar chave SSH para o novo usuário
rsync --archive --chown=deployer:deployer ~/.ssh /home/deployer
```

### 1.3 Configurar SSH (MAIS IMPORTANTE)
```bash
nano /etc/ssh/sshd_config
```
Altere/adicione estas linhas:
```
Port 2222                    # Mude a porta padrão!
PermitRootLogin no           # Bloquear root SSH
PasswordAuthentication no    # Só chave SSH
PubkeyAuthentication yes
MaxAuthTries 3
LoginGraceTime 20
```
```bash
systemctl restart sshd
# ⚠️ ANTES de desconectar, teste em outro terminal:
ssh -p 2222 deployer@SEU_IP
```

### 1.4 Firewall UFW
```bash
ufw default deny incoming
ufw default allow outgoing
ufw allow 2222/tcp      # SSH (porta personalizada)
ufw allow 80/tcp        # HTTP
ufw allow 443/tcp       # HTTPS
ufw enable
ufw status verbose
```

### 1.5 Fail2Ban (anti brute-force)
```bash
apt install -y fail2ban

cat > /etc/fail2ban/jail.local << 'EOF'
[DEFAULT]
bantime  = 3600
findtime = 600
maxretry = 3

[sshd]
enabled = true
port = 2222
filter = sshd
logpath = /var/log/auth.log

[nginx-http-auth]
enabled = true
EOF

systemctl enable fail2ban
systemctl start fail2ban
```

---

## 📦 PARTE 2 — INSTALAÇÃO DO STACK

### 2.1 Instalar PHP 8.2
```bash
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.2 php8.2-fpm php8.2-pgsql php8.2-mbstring php8.2-xml \
    php8.2-curl php8.2-zip php8.2-bcmath php8.2-gd php8.2-intl

# Verificar instalação
php -v
```

### 2.2 Instalar Nginx
```bash
apt install -y nginx
systemctl enable nginx
```

### 2.3 Instalar PostgreSQL 16
```bash
apt install -y postgresql postgresql-contrib
systemctl enable postgresql

# Criar banco e usuário com privilégios LIMITADOS
sudo -u postgres psql << 'SQL'
CREATE DATABASE sst_db;
CREATE USER sst_app WITH PASSWORD 'SENHA_FORTE_AQUI_16_CHARS';
GRANT ALL PRIVILEGES ON DATABASE sst_db TO sst_app;
ALTER USER sst_app CONNECTION LIMIT 50;
SQL

# PostgreSQL: aceitar conexão apenas localhost (padrão já é assim)
# Verificar: grep listen_addresses /etc/postgresql/16/main/postgresql.conf
# Deve ser: listen_addresses = 'localhost'
```

### 2.4 Instalar Composer
```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

---

## 🚀 PARTE 3 — DEPLOY DA APLICAÇÃO

### 3.1 Clonar/subir o projeto
```bash
mkdir -p /var/www/sst
chown -R deployer:www-data /var/www/sst

# Como usuário deployer:
su - deployer
cd /var/www/sst

# Opção A: Upload via SCP
# scp -P 2222 -r ./sst-final/* deployer@SEU_IP:/var/www/sst/

# Opção B: Git
# git clone git@github.com:seu_usuario/sst-manager.git .
```

### 3.2 Instalar dependências
```bash
cd /var/www/sst
composer install --optimize-autoloader --no-dev
```

### 3.3 Configurar .env
```bash
cp .env.example .env
nano .env
```
**Configure obrigatoriamente:**
```env
APP_NAME="SST Manager"
APP_ENV=production
APP_KEY=                    # Será gerado no próximo passo
APP_DEBUG=false             # NUNCA true em produção!
APP_URL=https://seudominio.com.br

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sst_db
DB_USERNAME=sst_app
DB_PASSWORD=SENHA_FORTE_AQUI_16_CHARS

SESSION_ENCRYPT=true
BCRYPT_ROUNDS=12
LOG_LEVEL=warning
```

### 3.4 Configurar Laravel
```bash
# Gerar chave da aplicação
php artisan key:generate

# Criar tabelas e dados demo
php artisan migrate --force
php artisan db:seed --force

# Link de uploads
php artisan storage:link

# Cache de performance (IMPORTANTE!)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Permissões corretas
chown -R www-data:www-data /var/www/sst/storage
chown -R www-data:www-data /var/www/sst/bootstrap/cache
chmod -R 775 /var/www/sst/storage
chmod -R 775 /var/www/sst/bootstrap/cache
```

---

## 🌐 PARTE 4 — CONFIGURAÇÃO NGINX

### 4.1 Virtual host
```bash
nano /etc/nginx/sites-available/sst-manager
```
```nginx
server {
    listen 80;
    server_name seudominio.com.br www.seudominio.com.br;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name seudominio.com.br www.seudominio.com.br;

    root /var/www/sst/public;
    index index.php;

    # SSL (configurado pelo Certbot abaixo)
    ssl_certificate     /etc/letsencrypt/live/seudominio.com.br/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/seudominio.com.br/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;
    ssl_prefer_server_ciphers on;
    ssl_session_cache   shared:SSL:10m;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    server_tokens off;

    # Block access to sensitive files
    location ~ /\.(env|git|htaccess) { deny all; }
    location ~* \.(sql|log|bak|backup)$ { deny all; }

    # Gzip
    gzip on;
    gzip_types text/plain text/css application/json application/javascript;

    # Static files cache
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Logs
    access_log /var/log/nginx/sst-manager.access.log;
    error_log  /var/log/nginx/sst-manager.error.log;
}
```
```bash
ln -s /etc/nginx/sites-available/sst-manager /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

---

## 🔒 PARTE 5 — HTTPS COM CERTBOT

```bash
apt install -y certbot python3-certbot-nginx

# Gerar certificado SSL gratuito
certbot --nginx -d seudominio.com.br -d www.seudominio.com.br

# Renovação automática (já configurada, mas verifique)
crontab -e
# Adicione:
# 0 3 * * * certbot renew --quiet

# Testar renovação
certbot renew --dry-run
```

---

## 💾 PARTE 6 — BACKUP AUTOMÁTICO

```bash
mkdir -p /opt/backups/sst

cat > /opt/backups/backup-sst.sh << 'BASH'
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/opt/backups/sst"
DB_USER="sst_app"
DB_NAME="sst_db"
APP_DIR="/var/www/sst"

# Backup do banco
PGPASSWORD="SENHA_AQUI" pg_dump -U $DB_USER $DB_NAME | gzip > "$BACKUP_DIR/db_$DATE.sql.gz"

# Backup dos uploads
tar -czf "$BACKUP_DIR/uploads_$DATE.tar.gz" "$APP_DIR/storage/app/public/"

# Manter apenas os últimos 30 backups
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete

echo "Backup concluído: $DATE"
BASH

chmod +x /opt/backups/backup-sst.sh

# Agendar backup diário às 2h
crontab -e
# Adicione:
# 0 2 * * * /opt/backups/backup-sst.sh >> /var/log/sst-backup.log 2>&1
```

---

## 📊 PARTE 7 — MONITORAMENTO

```bash
# Instalar htop
apt install -y htop

# Ver logs em tempo real
tail -f /var/log/nginx/sst-manager.error.log
tail -f /var/www/sst/storage/logs/laravel.log

# Ver status de todos os serviços
systemctl status nginx php8.2-fpm postgresql fail2ban

# Ver IPs banidos pelo Fail2Ban
fail2ban-client status sshd

# Ver conexões ativas
ss -tlnp | grep -E '(80|443|5432|2222)'
```

---

## 🔄 PARTE 8 — DEPLOY DE ATUALIZAÇÕES

```bash
# Script de deploy seguro
cat > /opt/deploy-sst.sh << 'BASH'
#!/bin/bash
set -e
cd /var/www/sst

echo "🚀 Iniciando deploy..."

# Ativar modo manutenção
php artisan down --message="Atualizando... Volte em breve." --retry=60

# Atualizar código
git pull origin main  # ou faça upload dos arquivos

# Instalar dependências
composer install --optimize-autoloader --no-dev --no-interaction

# Executar migrations
php artisan migrate --force

# Limpar e recriar cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Permissões
chown -R www-data:www-data storage bootstrap/cache

# Desativar modo manutenção
php artisan up

echo "✅ Deploy concluído!"
BASH
chmod +x /opt/deploy-sst.sh
```

---

## 🎯 CHECKLIST FINAL DE SEGURANÇA

```
✅ SSH na porta não-padrão (ex: 2222)
✅ Login root desabilitado no SSH
✅ Autenticação apenas por chave SSH (sem senha)
✅ UFW ativo com regras mínimas (80, 443, SSH)
✅ Fail2Ban ativo (anti brute-force)
✅ SSL/HTTPS ativo com Let's Encrypt
✅ APP_DEBUG=false em produção
✅ PostgreSQL ouvindo apenas localhost
✅ Usuário DB com permissões limitadas
✅ .env NÃO versionado no Git (.gitignore)
✅ Backup diário automatizado
✅ Headers de segurança no Nginx
✅ server_tokens off (ocultar versão do Nginx)
✅ Logs configurados e rotativos
✅ Multiempresa com tenant scope ativo
✅ CSRF token em todos os formulários (Laravel padrão)
✅ Senhas com bcrypt rounds=12
✅ Rate limiting no login (5 tentativas)
✅ Sessões encriptadas (SESSION_ENCRYPT=true)
```

---

## 🔑 Credenciais de acesso (demo)

| E-mail | Senha | Perfil |
|--------|-------|--------|
| admin@sst.com | password | Super Admin |
| tecnico@sst.com | password | Gestor |

> ⚠️ **Mude as senhas IMEDIATAMENTE após o primeiro login!**

---

## 📞 Comandos úteis para dia a dia

```bash
# Reiniciar serviços
systemctl restart nginx
systemctl restart php8.2-fpm
systemctl restart postgresql

# Ver logs da aplicação
tail -100 /var/www/sst/storage/logs/laravel.log

# Limpar cache manualmente
cd /var/www/sst && php artisan cache:clear && php artisan config:clear

# Rodar comando do Laravel
cd /var/www/sst && php artisan inspire

# Verificar versões
php -v && nginx -v && psql --version
```
