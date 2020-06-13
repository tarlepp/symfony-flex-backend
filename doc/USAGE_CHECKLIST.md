# What is this?

This document contains basic checklist what _you_ need to do if you're going to
use this template as in base of your own application.

## Table of Contents

* [What is this?](#what-is-this)
  * [Table of Contents](#table-of-contents)
  * [Checklist](#checklist)

## Checklist

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
  * [ ] Symfony Flex ID - this is important! First remove `extra.symfony.id`
        from your `composer.json` file and after that you just need to run
        `composer symfony:generate-id` command to generate new. Note that
        this will update your `composer.lock` file - so remember to commit
        that.
* [ ] Application configuration
  * [ ] [.env](../.env) Change `APP_SECRET` value with new one, you can use
        eg. [this](http://nux.net/secret) tool for that. Also remember to
        change that `APP_SECRET` value in [.env.test](../.env.test) and
  * [ ] [application.json](../secrets/application.json) Change file contents
        to match your application configuration - specially you need to
        generate new `JWT_PASSPHRASE` value. Also remember to do those
        changes to [application_test.json](../secrets/application_test.json)
        files.
* [ ] 3rd party services that you might not need _or_ you need to change those
      to work with _your_ application - if you don't need to use those services
      just delete those files and all is done.
  * [ ] [.codeclimate.yml](../.codeclimate.yml) - [https://codeclimate.com/](https://codeclimate.com/)
  * [ ] [.sensiolabs.yml](../.sensiolabs.yml) - [https://insight.symfony.com/](https://insight.symfony.com/)
  * [ ] [sonar-project.properties](../sonar-project.properties) - [https://sonarcloud.io/](https://sonarcloud.io/)
* [ ] Github Actions - This application is using GitHub Actions to run multiple
      jobs to check application code.
  * [ ] [main.yml](../.github/workflows/main.yml) - Check file contents and
        modify it for your needs.
* [ ] Last step when all above is done - just delete this file.

---

[Back to resources index](README.md) - [Back to main README.md](../README.md)
