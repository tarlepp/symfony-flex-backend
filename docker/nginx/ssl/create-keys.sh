#!/bin/bash

#
# Generate local development TLS materials for the Nginx configuration.
#
# Usage:
#   Run from this directory so openssl.cnf is found.
#
# Details:
#   Writes the CA and server certificate files here, replacing existing files
#   with the same names. Use these materials only for local development.
#

set -euo pipefail

# Create a local CA, then issue the Nginx certificate from that CA.
openssl genrsa -passout pass: -des3 -out rootCA.key 2048
openssl req -x509 -new -nodes -passin pass: -key rootCA.key \
    -sha256 -days 10000 -out rootCA.pem

openssl genrsa -out tls.key 2048
openssl req -new -key tls.key -out tls.csr
openssl x509 -req -in tls.csr -CA rootCA.pem -CAkey rootCA.key -CAcreateserial \
    -out tls.crt -days 10000 -sha256 -passin pass: -extfile openssl.cnf

# Optional PKCS#12 exports:
# openssl pkcs12 -export -inkey ./rootCA.key -in ./rootCA.pem -out ./rootCA.p12
# openssl pkcs12 -export -inkey ./tls.key -in ./tls.crt -out ./tls.p12
