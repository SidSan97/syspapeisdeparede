# Projeto Laravel

## Tecnologias

- Laravel 12
- Vue 3
- Laravel Passport
- FastBootstrap + Bootstrap 5 + Font Awesome 5
- PHPUnit Test Case/Test Coverage

## Instalação

- `cd laravel/`
- `composer install`
- `cp .env.example .env`
- Atualize o `.env` e defina as credenciais do banco de dados
- `php artisan key:generate`
- `php artisan migrate`
- `php artisan db:seed`
- `php artisan passport:keys`
- `npm install`
- `composer dev`

## Teste Unitário

#### executar PHPUnit

```bash
# execute PHPUnit todos os casos de teste
vendor/bin/phpunit
# ou apenas teste de recurso
vendor/bin/phpunit --testsuite Feature
```
