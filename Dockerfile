# Stage 1: Build Image for Composer dependencies
# Usamos una imagen base de Composer para instalar las dependencias
FROM composer:2 AS composer_install

# Establecemos el directorio de trabajo dentro del contenedor
WORKDIR /app

# Copiamos solo los archivos necesarios para Composer para optimizar el caché de Docker
COPY composer.json composer.lock ./

# Instalamos las dependencias de Composer, excluyendo las de desarrollo
# Esto mantiene la imagen final más pequeña y segura
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Stage 2: Production Image (Final Image)
# Usamos una imagen base de PHP-FPM con Alpine para un tamaño reducido y eficiencia
FROM php:8.3-fpm-alpine

# Establecemos el directorio de trabajo para la aplicación Laravel
WORKDIR /var/www/html

# Instalamos dependencias del sistema necesarias para Nginx, Supervisor y extensiones PHP
# Usamos apk add para Alpine Linux
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    git \
    mysql-client \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libzip-dev \
    libxml2-dev \
    oniguruma-dev \
    freetype-dev \
    npm \
    nodejs # Node y NPM son para assets, pueden ser movidos a la etapa de build si usas Vite/Mix

# Instalamos y habilitamos las extensiones PHP necesarias para Laravel
# -j$(nproc) usa todos los núcleos de CPU disponibles para una instalación más rápida
RUN docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql mbstring exif pcntl bcmath zip dom xml

# Copiamos todo el código de la aplicación desde el contexto local al contenedor
# Esto debe hacerse DESPUÉS de instalar las dependencias del sistema y PHP
COPY . .

# Copiamos las dependencias de Composer que se instalaron en la primera etapa
COPY --from=composer_install /app/vendor /var/www/html/vendor

# Generamos la clave de la aplicación.
# Esto se hace una vez durante la construcción de la imagen.
# En producción (Render), idealmente pasas APP_KEY como variable de entorno.
# Usamos --force para evitar la confirmación interactiva.
RUN php artisan key:generate --force

# Configuramos los permisos adecuados para los directorios de storage y cache de Laravel
# www-data es el usuario predeterminado de Nginx/PHP-FPM en muchas imágenes
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# OPCIONAL: Si necesitas correr migraciones durante el despliegue (no siempre recomendado en Dockerfile)
# Si tu base de datos no está disponible durante el build, esto fallará.
# Es mejor hacerlo como un comando separado en Render (Build Command o Start Command).
# RUN php artisan migrate --force

# --- Configuración de Nginx ---
# Eliminamos la configuración por defecto de Nginx
RUN rm -f /etc/nginx/http.d/default.conf
# Copiamos nuestra configuración de Nginx personalizada
COPY .docker/nginx.conf /etc/nginx/http.d/default.conf

# --- Configuración de Supervisor ---
# Copiamos nuestra configuración de Supervisor personalizada
COPY .docker/supervisord.conf /etc/supervisord.conf

# Exponemos el puerto 80, que es el puerto HTTP por defecto de Nginx
EXPOSE 80

# El comando principal que se ejecuta al iniciar el contenedor
# Supervisor se encargará de mantener Nginx y PHP-FPM corriendo
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]