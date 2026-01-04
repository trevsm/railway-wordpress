FROM wordpress:php8.2-apache

# Ensure only ONE Apache MPM is enabled (WordPress image expects prefork)
RUN a2dismod mpm_event mpm_worker || true \
 && a2enmod mpm_prefork \
 && a2enmod rewrite headers expires

COPY docker-entrypoint-railway.sh /usr/local/bin/docker-entrypoint-railway.sh
RUN chmod +x /usr/local/bin/docker-entrypoint-railway.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint-railway.sh"]
CMD ["apache2-foreground"]
