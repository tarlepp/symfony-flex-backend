FROM nginx:latest@sha256:86e53c4c16a6a276b204b0fd3a8143d86547c967dc8258b3d47c3a21bb68d3c6

ADD nginx.conf /etc/nginx/conf.d/default.conf
ADD php-upstream.conf /etc/nginx/conf.d/upstream.conf

# https://cloud.google.com/monitoring/agent/plugins/nginx
RUN apt-get update && apt-get install -y curl
RUN cd /etc/nginx/conf.d/ \
    && curl -O https://raw.githubusercontent.com/Stackdriver/stackdriver-agent-service-configs/master/etc/nginx/conf.d/status.conf \
    && rm -rf /var/lib/apt/lists/*
