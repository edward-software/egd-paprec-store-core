FROM php:7.4-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends locales apt-utils git libicu-dev g++ libpng-dev libxml2-dev  \
    libzip-dev libonig-dev libxslt-dev git libssl-dev librabbitmq-dev nano net-tools less gnupg npm

RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
RUN echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list
RUN apt-get update && apt-get install -y yarn

RUN a2enmod rewrite && a2dissite 000-default

RUN curl -sSk https://getcomposer.org/installer | php -- --disable-tls --version=2.0.8 && \
   mv composer.phar /usr/local/bin/composer

RUN docker-php-ext-configure intl
RUN docker-php-ext-install pdo pdo_mysql gd opcache intl zip calendar dom mbstring zip gd xsl
RUN pecl install apcu && docker-php-ext-enable apcu
RUN pecl install mongodb && docker-php-ext-enable mongodb
RUN pecl install amqp && docker-php-ext-enable amqp
RUN docker-php-ext-install sockets

#RUN curl "https://github.com/wkhtmltopdf/wkhtmltopdf/releases/download/0.12.5/wkhtmltox_0.12.5-1.buster_amd64.deb" -L -o "/tmp/wkhtmltox_0.12.5-1.buster_amd64.deb"
#RUN apt-get -y install /tmp/wkhtmltox_0.12.5-1.buster_amd64.deb
#RUN ln -s /usr/local/bin/wkhtmltopdf /usr/bin

COPY .bashrc /root/
COPY vhost.conf /etc/apache2/sites-enabled/local-store-core.paprec.com.conf

WORKDIR /var/www/
