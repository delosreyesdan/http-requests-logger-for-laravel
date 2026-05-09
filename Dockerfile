FROM php:8.2-cli-alpine

# Install dependencies: build tools + sqlite + redis extension
RUN apk add --no-cache \
    git \
    unzip \
    sqlite \
    sqlite-dev \
    autoconf \
    make \
    gcc \
    g++ \
    pkgconf \
    zlib-dev \
    && docker-php-ext-install pdo pdo_sqlite \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del autoconf make gcc g++ pkgconf

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

CMD ["composer", "test"]

