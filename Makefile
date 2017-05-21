ifndef APP_ENV
	include .env
endif

###> symfony/framework-bundle ###
cache-clear:
	@test -f bin/console && bin/console cache:clear --no-warmup || rm -rf var/cache/*
.PHONY: cache-clear

cache-warmup: cache-clear
	@test -f bin/console && bin/console cache:warmup || echo "cannot warmup the cache (needs symfony/console)"
.PHONY: cache-warmup

serve:
	@echo -e "\033[32;49mServer listening on http://127.0.0.1:8000\033[39m"
	@echo "Quit the server with CTRL-C."
	@echo -e "Run \033[32mcomposer require symfony/web-server-bundle\033[39m for a better web server"
	php -S 0.0.0.0:8000 -t web
.PHONY: serve
###< symfony/framework-bundle ###

###> lexik/jwt-authentication-bundle ###
generate-jwt-keys:
ifeq (, $(shell which openssl))
$(error "Unable to generate keys (needs OpenSSL)")
endif
	mkdir -p etc/jwt
	openssl genrsa -passout pass:${JWT_PASSPHRASE} -out ${JWT_PRIVATE_KEY_PATH} -aes256 4096
	openssl rsa -passin pass:${JWT_PASSPHRASE} -pubout -in ${JWT_PRIVATE_KEY_PATH} -out ${JWT_PUBLIC_KEY_PATH}
	@echo "\033[32mRSA key pair successfully generated\033[39m"
###< lexik/jwt-authentication-bundle ###
