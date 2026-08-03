FROM node:24-alpine AS frontend

WORKDIR /build

COPY package.json package-lock.json ./
RUN npm ci

COPY tailwind.config.js ./
COPY assets/tailwind.css ./assets/tailwind.css
COPY app ./app
COPY public ./public
COPY views ./views

RUN npm run build

FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . /var/www/html
COPY --from=frontend /build/dist/app.css /var/www/html/public/assets/app.css
COPY --from=frontend /build/dist/chart.umd.min.js /var/www/html/public/assets/chart.umd.min.js
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
