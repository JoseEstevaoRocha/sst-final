# SST Manager

Sistema web completo de Saúde e Segurança do Trabalho (SST) desenvolvido em Laravel 11.

Gerencia EPIs, ASOs, treinamentos, extintores, máquinas NR-12, brigada, CIPA e muito mais — com suporte a múltiplas empresas, controle de permissões por perfil e integração com a base oficial CAEPI do Ministério do Trabalho.

---

## Requisitos

| Ferramenta | Versão mínima |
|---|---|
| PHP | 8.2+ |
| Composer | 2.x |
| PostgreSQL | 14+ |

**Extensões PHP necessárias:** `pdo_pgsql`, `curl`, `zip`, `mbstring`, `openssl`, `xml`

> Sugestão: use o [Laravel Herd](https://herd.laravel.com) no Windows/Mac — instala PHP, Composer e servidor em um clique.

---

## Instalação (primeira vez)

### 1. Clonar o repositório

```bash
git clone <url-do-repositorio>
cd sst-manager
```

### 2. Criar o banco de dados

```sql
-- Via psql ou pgAdmin:
CREATE DATABASE sst_db;
```

### 3. Rodar o setup automático

Dê duplo clique em **`setup.bat`**. Ele pergunta qual modo usar:

```
[1] Instalação nova        → banco vazio + dados de demonstração
[2] Restaurar backup .sql  → importa seus dados reais (sem seed)
[3] Atualizar projeto      → só instala dependências novas + migrate
```

**Modo 2 — para quem já tem dados reais:**
- Informe o caminho do arquivo `.sql` (gerado pelo pg_dump ou pgAdmin)
- O script roda `migrate` (adiciona tabelas novas sem apagar nada) e depois importa o `.sql`
- Nenhum dado é perdido, nenhum seed é executado

**Modo 3 — para atualizar em máquina já configurada:**
- Só roda `composer install` + `migrate`
- Dados e `.env` existentes são preservados

### 4. Configurar o `.env`

O setup abre o arquivo automaticamente. Preencha:

```env
DB_HOST=127.0.0.1
DB_DATABASE=sst_db
DB_USERNAME=postgres
DB_PASSWORD=sua_senha
```

> Para banco em nuvem (Supabase, etc): use as credenciais fornecidas pelo serviço.

---

## Iniciar o sistema

Após o setup, para rodar o sistema do dia a dia:

**Dê duplo clique em `start-sst.vbs`** (sem janela de terminal) ou execute:

```
start-sst.bat
```

O sistema abre automaticamente em `http://localhost:8000`.

Para parar:

```
stop-sst.bat
```

---

## Credenciais de demonstração

| E-mail | Senha | Perfil |
|---|---|---|
| admin@sst.com | password | Super Admin (acesso total) |
| tecnico@sst.com | password | Gestor (empresa MetalSP) |

---

## Estrutura do projeto

```
sst-manager/
├── app/
│   ├── Console/Commands/     # Comandos Artisan (CAEPI sync, backup, agendamentos)
│   ├── Http/
│   │   ├── Controllers/      # Um controller por módulo do sistema
│   │   └── Middleware/       # EnsureTenantScope, SecurityHeaders
│   ├── Models/               # Eloquent models (BaseModel com escopo multi-empresa)
│   ├── Services/             # CaEpiService, NfEntradaService, BackupService
│   ├── Exports/              # Exportações Excel (Maatwebsite)
│   └── Helpers/              # CnaeRiscoHelper
├── config/                   # Configurações Laravel (app, auth, database, etc.)
├── database/
│   ├── migrations/           # 24 migrations organizadas cronologicamente
│   └── seeders/              # DatabaseSeeder (dados demo) + RolesPermissionsSeeder
├── public/                   # Ponto de entrada web (index.php, assets CSS/JS)
├── resources/views/          # Blade templates por módulo
├── routes/
│   ├── web.php               # Todas as rotas web (auth, dashboard, módulos)
│   └── api.php               # Rotas da API interna (AJAX)
├── storage/                  # Logs, cache, sessões, uploads, backups
├── setup.bat                 # Instalação inicial (rodar UMA VEZ ao clonar)
├── start-sst.bat             # Inicia o servidor + scheduler + sync CAEPI
├── start-sst.vbs             # Idem, sem abrir janela de terminal
├── stop-sst.bat              # Para o servidor e limpa caches
├── caepi-sync.vbs            # Sincroniza base CAEPI do MTE manualmente
├── schedule-worker.vbs       # Mantém o agendador Laravel rodando em background
└── php-server.vbs            # Inicia o servidor PHP em background (sem janela)
```

---

## Módulos do sistema

| Módulo | Descrição |
|---|---|
| Dashboard | Alertas, estatísticas, gráficos em tempo real |
| Colaboradores | Cadastro, histórico, demissão, ficha completa |
| EPIs | Catálogo, estoque, entregas, validade de CA, NF de entrada |
| CAEPI | Integração com base oficial do MTE (ftp.mtps.gov.br) |
| ASOs | Exames ocupacionais, agendamento, controle de vencimento |
| Uniformes | Catálogo, estoque por tamanho, entregas |
| GHE / Riscos | Grupos Homogêneos de Exposição, matriz de riscos |
| Máquinas NR-12 | Cadastro, manutenções preventivas/corretivas, checklist |
| Extintores | Controle, inspeções, alertas de vencimento |
| Brigada / CIPA | Membros, funções, treinamentos |
| Relatórios | ASOs, EPIs, uniformes, extintores, máquinas — exportação Excel/PDF |
| Configurações | Usuários, permissões por perfil, backup automático |

---

## Comandos úteis

```bash
# Limpar todos os caches (necessário após alterar .env ou rotas)
php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan cache:clear

# Ver todas as rotas registradas
php artisan route:list

# Sincronizar base CAEPI manualmente
php artisan caepi:sincronizar

# Resetar banco e recriar dados demo (APAGA TUDO)
php artisan migrate:fresh --seed

# Listar usuários e permissões
php artisan tinker
>>> App\Models\User::all(['name','email'])
```

---

## Solução de problemas

| Erro | Causa provável | Solução |
|---|---|---|
| `could not find driver` | Extensão `pdo_pgsql` desabilitada | No `php.ini`, remova o `;` antes de `extension=pdo_pgsql` |
| `SQLSTATE[08006]` conexão recusada | PostgreSQL não está rodando | Inicie o serviço PostgreSQL |
| `APP_KEY not set` | .env sem chave gerada | `php artisan key:generate` |
| `Route not defined` | Cache de rotas desatualizado | `php artisan route:clear` |
| Página em branco / erro 500 | Erro na aplicação | Veja `storage/logs/laravel.log` |
| `Permission denied` em storage | Permissões de pasta | `chmod -R 775 storage bootstrap/cache` (Linux/Mac) |
| CAEPI não sincroniza | Extensão `curl` ou `zip` desabilitada | Habilite no `php.ini` |

---

## Produção / VPS

Consulte **`DEPLOY_VPS.md`** — guia completo com Nginx, HTTPS, PostgreSQL isolado, Fail2Ban e backup automático.

---

## Tecnologias

- **Backend:** Laravel 11, PHP 8.2+, PostgreSQL 14+
- **Autenticação e permissões:** Laravel Auth + Spatie Permission
- **PDF:** barryvdh/laravel-dompdf
- **Excel:** Maatwebsite/Laravel-Excel
- **CAEPI:** Download via cURL do FTP oficial do MTE (`ftp.mtps.gov.br`)
- **Frontend:** Bootstrap 5, Font Awesome, JS vanilla (sem build step)
