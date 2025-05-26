FROM php:8.2-cli

WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install zip pdo pdo_mysql

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy files
COPY . .

# Install dependencies
RUN composer install --ignore-platform-reqs --no-interaction --prefer-dist --optimize-autoloader
RUN npm ci
RUN npm run build

# Set environment
ENV APP_ENV=production
ENV APP_DEBUG=false

# Generate key if not set (will need to be set properly in production)
RUN php artisan key:generate --ansi

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]