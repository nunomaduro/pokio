FROM php:8.3-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
  libffi-dev \
  libzip-dev \
  unzip \
  git \
  aspell \
  aspell-en \
  && docker-php-ext-install ffi zip pcntl posix \
  && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN pecl install xdebug \
  && docker-php-ext-enable xdebug \
  && echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Enable FFI in PHP via ini
RUN echo "ffi.enable=1" > /usr/local/etc/php/conf.d/ffi.ini

WORKDIR /app

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json /app/

COPY . /app