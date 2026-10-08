# Development-only test runner for theme_chinijo.
#
# It reproduces the GitHub Actions jobs locally: Moodle HQ's PHP image (with
# PCOV), Composer, nvm/Node and moodle-plugin-ci. It is never part of the
# theme or of the production stack.
ARG PHP_VERSION=8.4
FROM moodlehq/moodle-php-apache:${PHP_VERSION}-bookworm

# Java runs the HTML validator used by the Mustache lint, as on GitHub's runners.
RUN apt-get update \
    && apt-get install -y --no-install-recommends rsync postgresql-client default-jre-headless \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.10.3 /usr/bin/composer /usr/bin/composer

ENV NVM_DIR=/opt/nvm
RUN mkdir -p "$NVM_DIR" \
    && curl -fsSL https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.8/install.sh | PROFILE=/dev/null bash \
    && . "$NVM_DIR/nvm.sh" \
    && nvm install 22 \
    && nvm alias default 22

ARG MOODLE_PLUGIN_CI_VERSION=4.5.11
# moodle-plugin-ci runs npm while it is installed.
RUN . "$NVM_DIR/nvm.sh" \
    && composer create-project -n --no-dev --prefer-dist moodlehq/moodle-plugin-ci /opt/ci "${MOODLE_PLUGIN_CI_VERSION}" \
    && composer clear-cache

ENV PATH=/opt/ci/bin:/opt/ci/vendor/bin:$PATH \
    COMPOSER_ALLOW_SUPERUSER=1

RUN printf 'memory_limit=1024M\nmax_input_vars=5000\n' > /usr/local/etc/php/conf.d/99-chinijo-ci.ini

COPY run.sh /usr/local/bin/chinijo-ci
RUN chmod +x /usr/local/bin/chinijo-ci

# Run as an unprivileged user. The /work volume takes its ownership from this directory.
RUN useradd --create-home --uid 1000 ci \
    && mkdir -p /work /out \
    && chown -R ci:ci /work /out /opt/ci /opt/nvm
USER ci

WORKDIR /work
ENTRYPOINT ["/usr/local/bin/chinijo-ci"]
CMD ["help"]
