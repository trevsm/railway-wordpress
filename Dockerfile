FROM wordpress:php8.2-apache

RUN a2enmod rewrite headers expires

# Wrap apache2-foreground so we can fix MPM at runtime (Railway re-enables modules)
RUN mv /usr/local/bin/apache2-foreground /usr/local/bin/apache2-foreground.orig
COPY apache2-foreground-railway /usr/local/bin/apache2-foreground
RUN chmod +x /usr/local/bin/apache2-foreground

COPY docker-entrypoint-railway.sh /usr/local/bin/docker-entrypoint-railway.sh
RUN chmod +x /usr/local/bin/docker-entrypoint-railway.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint-railway.sh"]
CMD ["apache2-foreground"]
