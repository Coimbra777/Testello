# Testello - Sistema de Importação de Tabelas de Frete

Sistema Laravel 11 para importação de CSV com processamento assíncrono.

## Requisitos Atendidos

✅ Estrutura do Banco Normalizada
✅ Importação de CSV com Validação
✅ PHP 8 + Laravel 11
✅ PSR-1 e PSR-12
✅ Clean Code
✅ Docker

## Tecnologias

-   PHP 8.2+ | Laravel 11 | MySQL 8.0 | Redis | Docker

## Como Rodar

### Pré-requisitos

-   Docker e Docker Compose

### Comandos

# 1. Clone

```bash
git clone https://github.com/Coimbra777/Testello.git
```
```bash
cd Testello
```

# 2. Configure

```bash
cp .env.example .env
```

# 3. Suba containers
```bash
docker compose up -d --build
```

# 4. Instale dependências

```bash
docker compose exec app bash
```

```bash
composer install
```

# 5. Configure aplicação

```bash
php artisan key:generate
```
```bash
php artisan migrate
```

## Antes de rodar seeders, teste importando o arquivo via interface web

```bash
php artisan db:seed
```

## 🌐 Acessos

-   **Interface Web**: http://localhost:8000
-   **PHPMyAdmin**: http://localhost:8080 (root/userpass)

## Testes

```bash
docker compose exec app php artisan test
```

## Se estiver dentro do container

```bash
php artisan test
```

## 📋 Como Usar

1. Acesse http://localhost:8000
2. Arraste arquivo CSV ou clique para selecionar
3. Preencha nome e documento do cliente
4. Clique "Importar"
