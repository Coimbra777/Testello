# 🚛 Testello - Sistema de Importação de Tabelas de Frete

Sistema Laravel 11 para importação de CSV com processamento assíncrono.

## 🎯 Requisitos Atendidos

✅ Estrutura do Banco Normalizada
✅ Importação de CSV com Validação
✅ PHP 8 + Laravel 11
✅ PSR-1 e PSR-12
✅ Clean Code
✅ Docker

## 🛠️ Tecnologias

-   PHP 8.2+ | Laravel 11 | MySQL 8.0 | Redis | Docker

## 📦 Como Rodar

### Pré-requisitos

-   Docker e Docker Compose

### Comandos

```bash
# 1. Clone
git clone https://github.com/Coimbra777/Testello.git
cd app-laravel

# 2. Configure
cp .env.example .env

# 3. Suba containers
docker compose up -d --build

# 4. Instale dependências
docker compose exec app composer install

# 5. Configure aplicação
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
```

## 🌐 Acessos

-   **Interface Web**: http://localhost:8000
-   **API Health**: http://localhost:8000/api/health
-   **PHPMyAdmin**: http://localhost:8080 (root/root)

## 🧪 Testes

```bash
docker compose exec app php artisan test
```

## 📋 Como Usar

1. Acesse http://localhost:8000
2. Arraste arquivo CSV ou clique para selecionar
3. Preencha nome e documento do cliente
4. Clique "Importar"

### Formato CSV

```csv
min_weight,max_weight,price
0,1.5,10.50
1.5,3.0,15.75
```

### API Básica

```bash
curl -X POST http://localhost:8000/api/freight/import \
  -F "csv_file=@arquivo.csv" \
  -F "client_name=Cliente" \
  -F "client_document=12345678901234"
```

---

**Laravel 11 + Docker**
