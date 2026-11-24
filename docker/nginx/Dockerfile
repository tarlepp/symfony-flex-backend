FROM nginx:1.31.10-alpine-slim@sha256:c293b28729906d97fa5302fce3bd79bd27a3ea9a5d9604e21c6ff0ebf14549fc

# Compose mounts nginx-dev.conf over this production configuration for development.
ADD nginx.conf /etc/nginx/conf.d/default.conf
ADD php-upstream.conf /etc/nginx/conf.d/upstream.conf

# https://cloud.google.com/monitoring/agent/plugins/nginx
RUN apk update && apk add curl
RUN cd /etc/nginx/conf.d/ \
    && curl -O https://raw.githubusercontent.com/Stackdriver/stackdriver-agent-service-configs/master/etc/nginx/conf.d/status.conf \
    && rm -rf /var/cache/apk/*
