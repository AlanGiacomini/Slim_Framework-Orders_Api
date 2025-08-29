# Usa uma imagem base oficial do PHP 8.2 com Apache
FROM php:8.2-apache

# Define o diretório de trabalho do contêiner.
# Todos os comandos a seguir serão executados neste diretório.
WORKDIR /var/www/html/

# Construção da aplicação: Múltiplos estágios para otimizar o cache
# Instala o Composer de uma imagem separada para manter a imagem final menor.
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copia os arquivos de configuração do Composer primeiro.
# Isso garante que a camada de dependências só seja reconstruída se esses arquivos mudarem.
COPY composer.json composer.lock ./

# linha para instalar as dependências do sistema
RUN apt-get update && apt-get install -y git unzip

# --- Configuração do Ambiente e Dependências PHP ---
# Instala extensões PHP necessárias para o projeto.
# pdo e pdo_mysql são essenciais para a conexão com o banco de dados.
RUN docker-php-ext-install pdo pdo_mysql bcmath

# Instala as dependências do projeto.
# As flags são essenciais para ambientes de build automatizados.
RUN composer install --no-interaction --prefer-dist

# Copia o restante dos arquivos da aplicação.
COPY . /var/www/html/

# Ativa o módulo de reescrita de URL do Apache (mod_rewrite),
# que é necessário para as rotas da sua API Slim.
RUN a2enmod rewrite

# Altera o DocumentRoot do Apache para a pasta 'public',
# protegendo o código-fonte da sua aplicação.
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Dá as permissões corretas para o Apache (usuário 'www-data')
# acessar e gerenciar os arquivos da aplicação.
RUN chown -R www-data:www-data /var/www/html

# --- Execução do Contêiner ---
# Inicia o Apache no modo 'foreground',
# permitindo que o Docker monitore o processo.
CMD ["apache2-foreground"]

# Expõe a porta 80 do contêiner.
EXPOSE 80