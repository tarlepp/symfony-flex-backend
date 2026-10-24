FROM nginx:1.31.8-alpine-slim@sha256:b899b0ef5345e14c01720a5ff83e716bacda65c7077a7363602ff7e7970bf88a

# Compose mounts nginx-dev.conf over this production configuration for development.
ADD nginx.conf /etc/nginx/conf.d/default.conf
ADD php-upstream.conf /etc/nginx/conf.d/upstream.conf

# https://cloud.google.com/monitoring/agent/plugins/nginx
RUN apk update && apk add curl
RUN cd /etc/nginx/conf.d/ \
    && curl -O https://raw.githubusercontent.com/Stackdriver/stackdriver-agent-service-configs/master/etc/nginx/conf.d/status.conf \
    && rm -rf /var/cache/apk/*
