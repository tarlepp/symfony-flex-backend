# What is this?

This document contains basic checklist what _you_ need to do if you're going to
use this template as in base of your own application.

## Table of Contents

* [What is this?](#what-is-this)
  * [Table of Contents](#table-of-contents)
  * [Checklist](#checklist-table-of-contents)

## Checklist [ᐞ](#table-of-contents)

Below you have _basic_ checklist that **_you need to go through_** after you have
started to use this template.

* [ ] Check that [LICENSE](../LICENSE) matches to your needs and change it if
      needed.
* [ ] Check that [README.md](../README.md) contains only things related to your
      application.
* [ ] Update [composer.json](../composer.json) to match with your application.
      Below you see the parts that you should check/update;
  * [ ] Common properties; `name`, `description`, `keywords`, `homapage`,
        `version`, `license`, `authors`, `support.issues` and
        `extra.projectTitle`
* [ ] Application configuration / setup
  * [ ] [.env](../.env) Change `APP_SECRET` value with new one, you can use
        eg. [this](http://nux.net/secret) tool for that. Also remember to
        change that `APP_SECRET` value in [.env.test](../.env.test) and in
        [.env.gh-actions](../.env.gh-actions) files.
  * [ ] [application.json](../secrets/application.json) Change file contents
        to match your application configuration - specially you need to
        generate new `JWT_PASSPHRASE` value. Also remember to do those
        changes to [application_test.json](../secrets/application_test.json)
        files.
  * [ ] [site.webmanifest](../public/site.webmanifest) - check that file
        contents - [information](https://developer.mozilla.org/en-US/docs/Web/Manifest)
        - and made necessary changes.
  * [ ] [robots.txt](../public/robots.txt) - check that file contents -
        [information](https://developers.google.com/search/docs/advanced/robots/intro)
        - and made necessary changes.
  * [ ] [favicon.ico](../public/favicon.ico) - change your application favicon
        to match your brand.
  * [ ] [docker-compose.yml](../docker-compose.yml) Change `container_name` to
        match your application.
  * [ ] [mysql_custom.cnf](../docker/mysql/mysql_custom.cnf) Check that MySQL
        has custom configuration that your application needs. Also check that
        [Dockerfile](../docker/mysql/Dockerfile) is matching your production
        setup.
  * [ ] [nginx.conf](../docker/nginx/nginx.conf) Check that Nginx has proper
        configuration for your application needs. Also check that
        [Dockerfile](../docker/mysql/Dockerfile) is matching your production
        setup.
  * [ ] [php.ini](../docker/php/php.ini) Check that PHP has proper production
        configuration. Also check [php-dev.ini](../docker/php/php-dev.ini) for
        development environment setup.
* [ ] 3rd party services that you might not need _or_ you need to change those
      to work with _your_ application - if you don't need to use those services
      just delete those files and all is done.
  * [ ] [.codeclimate.yml](../.codeclimate.yml) - [https://codeclimate.com/](https://codeclimate.com/)
  * [ ] [sonar-project.properties](../sonar-project.properties) - [https://sonarcloud.io/](https://sonarcloud.io/)
* [ ] Github Actions - This application is using GitHub Actions to run multiple
      jobs to check application code.
  * [ ] [main.yml](../.github/workflows/main.yml) - Check file contents and
        modify it for your needs.
  * [ ] [vulnerability-scan.yml](../.github/workflows/vulnerability-scan.yml) -
        Check file contents and modify it for your needs.
* [ ] Last step when all above is done - just delete this file.

---

[Back to resources index](README.md) - [Back to main README.md](../README.md)
