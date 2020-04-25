FROM nginx:latest

ARG HOST_UID
ARG HOST_GID

ADD nginx.conf /etc/nginx/conf.d/default.conf
ADD php-upstream.conf /etc/nginx/conf.d/upstream.conf

# https://cloud.google.com/monitoring/agent/plugins/nginx
RUN apt-get update && apt-get install -y curl
RUN cd /etc/nginx/conf.d/ \
    && curl -O https://raw.githubusercontent.com/Stackdriver/stackdriver-agent-service-configs/master/etc/nginx/conf.d/status.conf \
    && rm -rf /var/lib/apt/lists/*

RUN usermod -u ${HOST_UID} www-data
