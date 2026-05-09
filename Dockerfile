FROM php:8.3

RUN apt-get update && apt-get install -y \
    libc6-dev \
    libsasl2-dev \
    libsasl2-modules \
    libssl-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype-dev \
    zip


RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql zip exif gd
RUN docker-php-ext-enable exif gd

RUN curl -sS https://getcomposer.org/installer | php -- \
        --install-dir=/usr/local/bin --filename=composer

RUN apt-get update && apt-get install -y libxml2-dev \
    && docker-php-ext-install soap

USER 1000:1000


WORKDIR /app
