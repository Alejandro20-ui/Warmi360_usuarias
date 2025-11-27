FROM php:8.2-cli

# Instalar PDO MySQL
RUN docker-php-ext-install pdo_mysql

# Copiar proyecto
COPY . /app

# Cambiar directorio
WORKDIR /app

# Ejecutar servidor PHP interno
CMD ["php", "-S", "0.0.0.0:8080", "-t", "."]