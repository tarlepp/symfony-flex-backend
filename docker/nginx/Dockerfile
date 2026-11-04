FROM nginx:1.31.8-alpine-slim@sha256:f45171d36c76a8b2f5ef97108a3a8a2294248a6578a0a180edc2d78f7d2edc26

# Compose mounts nginx-dev.conf over this production configuration for development.
ADD nginx.conf /etc/nginx/conf.d/default.conf
ADD php-upstream.conf /etc/nginx/conf.d/upstream.conf

# https://cloud.google.com/monitoring/agent/plugins/nginx
RUN apk update && apk add curl
RUN cd /etc/nginx/conf.d/ \
    && curl -O https://raw.githubusercontent.com/Stackdriver/stackdriver-agent-service-configs/master/etc/nginx/conf.d/status.conf \
    && rm -rf /var/cache/apk/*
