# PHP 8.2 reaches end of security support on 2026-12-31, so run a newer supported PHP.
FROM wordpress:php8.4-apache

RUN a2enmod rewrite headers expires

# Web server hardening: security headers, hidden versions, blocked bot/info endpoints,
# no PHP execution from uploads (see file for details)
COPY railway-hardening.conf /etc/apache2/conf-available/railway-hardening.conf
RUN a2enconf railway-hardening

# PHP hardening: hide version, no error display, restrict shell-exec functions
COPY php-hardening.ini /usr/local/etc/php/conf.d/zz-railway-hardening.ini

# Must-use plugins, copied into the persistent wp-content volume at startup
COPY mu-plugins /usr/local/share/railway/mu-plugins

# Wrap apache2-foreground so we can fix MPM at runtime (Railway re-enables modules)
RUN mv /usr/local/bin/apache2-foreground /usr/local/bin/apache2-foreground.orig
COPY apache2-foreground-railway /usr/local/bin/apache2-foreground
RUN chmod +x /usr/local/bin/apache2-foreground

COPY docker-entrypoint-railway.sh /usr/local/bin/docker-entrypoint-railway.sh
RUN chmod +x /usr/local/bin/docker-entrypoint-railway.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint-railway.sh"]
CMD ["apache2-foreground"]
