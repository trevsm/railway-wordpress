# Railway-focused WordPress container:
# - Runs Apache + PHP (official WordPress base)
# - Moves wp-content onto a Railway Volume
# - Uses env vars for DB config (Railway MySQL plugin or external DB)

FROM wordpress:php8.2-apache

# Enable common Apache modules used by WordPress setups
RUN a2enmod rewrite headers expires

# Put our bootstrap script in place
COPY docker-entrypoint-railway.sh /usr/local/bin/docker-entrypoint-railway.sh
RUN chmod +x /usr/local/bin/docker-entrypoint-railway.sh

# Use the official WordPress entrypoint behavior, but with our volume wiring first
ENTRYPOINT ["docker-entrypoint-railway.sh"]
CMD ["apache2-foreground"]
