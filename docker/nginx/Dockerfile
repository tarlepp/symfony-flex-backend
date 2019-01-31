FROM nginx:latest

ADD nginx.conf /etc/nginx/conf.d/default.conf
ADD php-upstream.conf /etc/nginx/conf.d/upstream.conf

RUN usermod -u 1000 www-data
