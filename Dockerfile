FROM neunerlei/php:7.4-fpm-alpine

ARG DOCKER_RUNTIME
ARG DOCKER_GID
ARG DOCKER_UID
ENV DOCKER_RUNTIME=${DOCKER_RUNTIME:-docker}

ENV APP_ENV=dev
ENV COMPOSER_HOME=/opt/composer
ENV COMPOSER_MAX_PARALLEL_HTTP=1

# We need this as compatiblity for podman users
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN --mount=type=cache,id=apk-cache,target=/var/cache/apk rm -rf /etc/apk/cache && ln -s /var/cache/apk /etc/apk/cache && \
	apk update && apk upgrade && apk add \
	libxml2-dev \
	git \
	libzip-dev \
	zip \
	unzip

RUN docker-php-ext-install \
	zip \
	intl

RUN --mount=type=cache,id=apk-cache,target=/var/cache/apk rm -rf /etc/apk/cache && ln -s /var/cache/apk /etc/apk/cache && \
	apk update && apk upgrade && apk add --no-cache $PHPIZE_DEPS \
      && pecl install xdebug-3.0.0 \
      && docker-php-ext-enable xdebug

COPY --from=index.docker.io/library/composer:latest /usr/bin/composer /usr/bin/composer

RUN --mount=type=cache,id=apk-cache,target=/var/cache/apk rm -rf /etc/apk/cache && ln -s /var/cache/apk /etc/apk/cache && \
	apk update && apk upgrade && apk add shadow

RUN if [ "${DOCKER_RUNTIME}" = "docker" ]; \
      then \
        (userdel -r www-data || true) \
        && (userdel ${DOCKER_UID} || true) \
        && (groupdel -f www-data || true) \
        && (groupdel ${DOCKER_GID} || true) \
        && groupadd -g ${DOCKER_GID} www-data \
        && adduser -u ${DOCKER_UID} -D -S -G www-data www-data ; \
    fi

RUN mkdir -p /opt/project
RUN mkdir -p /opt/composer
RUN if [ "${DOCKER_RUNTIME}" = "docker" ] ; then chown ${DOCKER_UID}:${DOCKER_GID} /opt/composer ; fi

WORKDIR /opt/project

USER ${DOCKER_UID}:${DOCKER_GID}

USER root
