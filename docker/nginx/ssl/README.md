# What is this?

This folder contains all docker configuration for local development
environment.

## Table of Contents

* [What is this?](#what-is-this)
  * [Table of Contents](#table-of-contents)
  * [Selfsigned SSL certificates](#selfsigned-ssl-certificates-ᐞ)

## Selfsigned SSL certificates [ᐞ](#table-of-contents)

Note that this directory contains following SSL certification files:

* [rootCA.key](rootCA.key)
* [rootCA.pem](rootCA.pem)
* [tls.crt](tls.crt)
* [tls.csr](tls.csr)
* [tls.key](tls.key)

And these are just for _local_ development environment and these should be
used in _production_ environment. These certificates are valid until 2050,
so I think that is long enough - and if not I'll update those certificates.

Because application is running on `https` by default now, you will see
security issue on your browser when you access `https://localhost:8000` url.
You see this issue because of these selfsigned certificates. To solve this
issue you've basically two choices:

1. Just ignore that security issue (easy way)
2. Import that [rootCA.pem](rootCA.pem) to your browser as a trusted root
certificate (proper way)

For that second option see eg.
[this](https://dgu2000.medium.com/working-with-self-signed-certificates-in-chrome-walkthrough-edition-a238486e6858)
article - Specially that `Step 4: Adding CA as trusted to Chrome` part.

---

[Back to main README.md](../../../README.md)