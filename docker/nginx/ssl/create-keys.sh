#!/bin/bash

openssl genrsa -passout pass: -des3 -out rootCA.key 2048
openssl req -x509 -new -nodes -passin pass: -key rootCA.key -sha256 -days 10000 -out rootCA.pem
openssl genrsa -out tls.key 2048
openssl req -new -key tls.key -out tls.csr
openssl x509 -req -in tls.csr -CA rootCA.pem -CAkey rootCA.key -CAcreateserial -out tls.crt -days 10000 -sha256 -passin pass: -extfile openssl.cnf
