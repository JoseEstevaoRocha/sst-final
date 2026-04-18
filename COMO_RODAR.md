# 🚀 SST Manager — Como Rodar Localmente (Sem VSCode)

## ✅ Pré-requisitos

| Ferramenta | Versão | Download |
|-----------|--------|---------|
| PHP | 8.2+ | https://php.net/downloads |
| Composer | 2.x | https://getcomposer.org |
| PostgreSQL | 14+ | https://postgresql.org/download |

> **Dica rápida:** Instale o **Laravel Herd** (https://herd.laravel.com) — inclui PHP, Composer e banco, instala em 1 clique no Windows/Mac.

---

## 📦 Instalação em 6 passos

### 1. Criar o banco de dados
```bash
# Via terminal PostgreSQL
psql -U postgres -c "CREATE DATABASE sst_db;"

# OU via pgAdmin: Database > Create > Name: sst_db
```

### 2. Instalar dependências PHP
```bash
# Na pasta do projeto:
composer install
```

### 3. Configurar o .env
```bash
cp .env.example .env
```
Edite o arquivo `.env` com bloco de notas e ajuste:
```env
DB_DATABASE=sst_db
DB_USERNAME=postgres
DB_PASSWORD=sua_senha
```

### 4. Gerar chave + criar tabelas + dados demo
```bash
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
```

### 5. Iniciar servidor de desenvolvimento
```bash
php artisan serve
```

### 6. Acessar no navegador
```
http://localhost:8000
Login: admin@sst.com / password
```

---

## 🔑 Credenciais demo
| E-mail | Senha | Perfil |
|--------|-------|--------|
| admin@sst.com | password | Super Admin |
| tecnico@sst.com | password | Gestor |

---

## 🛠️ Comandos úteis

```bash
# Resetar tudo (apaga dados e recria)
php artisan migrate:fresh --seed

# Limpar cache
php artisan cache:clear && php artisan config:clear && php artisan view:clear

# Ver todas as rotas
php artisan route:list

# Shell interativo do Laravel
php artisan tinker
```

---

## ❗ Solução de problemas

| Erro | Solução |
|------|---------|
| `could not find driver` | No php.ini, descomente `extension=pdo_pgsql` |
| `SQLSTATE[08006]` conexão recusada | Verifique se o PostgreSQL está rodando |
| `APP_KEY not set` | Rode `php artisan key:generate` |
| Página em branco | Veja `storage/logs/laravel.log` |
| `Permission denied` em storage | `chmod -R 775 storage bootstrap/cache` |

---

## 🌐 Para o VPS/Produção

Consulte o arquivo **`DEPLOY_VPS.md`** — guia completo com:
- Configuração SSH segura
- Firewall UFW + Fail2Ban
- Nginx + HTTPS com Let's Encrypt
- PostgreSQL isolado
- Backup automático
- Script de deploy
