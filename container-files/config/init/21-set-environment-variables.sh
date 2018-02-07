#!/bin/sh

# stub in environment variables into config files
sed -i "s/<app_env_placeholder>/$APP_ENV/g" /etc/nginx/fastcgi_params
sed -i "s/<db_name_placeholder>/$MYSQL_DATABASE/g" /etc/nginx/fastcgi_params
sed -i "s/<db_user_placeholder>/$MYSQL_USER/g" /etc/nginx/fastcgi_params
sed -i "s/<db_pass_placeholder>/$MYSQL_PASSWORD/g" /etc/nginx/fastcgi_params
sed -i "s/<db_host_placeholder>/$MYSQL_HOST/g" /etc/nginx/fastcgi_params