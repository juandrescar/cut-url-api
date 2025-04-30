<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# Cut URL - Proyecto completo (Laravel + NestJS + Vue)
## Description

- `api-laravel/`: API principal desarrollada en Laravel.

## Repo del proyecto completo
El proyecto completo lo puede ver en el siguiente link
```bash
git clone https://github.com/juandrescar/curl-project.git
```

## Clonar solo el proyecto de la api de laravel
```bash
git clone https://github.com/juandrescar/cut-url-api.git
```

## Configurar el contenedor Laravel:
1. Entrar en el contenedor de laravel
```bash
docker compose exec cut-url-api bash
```
2. Instalar dependencias PHP:
```bash
composer update
```
3. Copiar y renombrar el archivo de variables de entorno:
```bash
cp .env.example .env
```

4. Generar la clave de aplicación:
```bash
php artisan key:generate
```

5. Generar clave de JWT
```bash
php artisan jwt:secret
```

6. Verifica el nombre de la base de datos (no cambiar este valor):

```ini
DB_DATABASE=url_shortener
```
7. Ejecutar las migraciones:
```bash
php artisan migrate
```
8. Verificar configuración de RabbitMQ
```ini
QUEUE_CONNECTION=rabbitmq

RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
RABBITMQ_VHOST=/
```