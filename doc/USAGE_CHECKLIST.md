# What is this?

This document contains basic checklist what _you_ need to do if you're going to
use this template as in base of your own application.

## Table of Contents

* [What is this?](#what-is-this)
   * [Table of Contents](#table-of-contents)
   * [Checklist](#checklist)

## Checklist

* [ ] Check that [LICENSE](../LICENSE) matches to your needs and change it if
      needed.
* [ ] Check that [README.md](../README.md) contains only things related to your
      application. 
* [ ] Update [composer.json](../composer.json) to match with your application.
      Below you see the points that you should check/update;
    * [ ] Common parts; `name`, `description`, `keywords`, `homapage`, 
          `version`, `license`, `authors`, `support.issues` and
          `extra.projectTitle`
    * [ ] Symfony Flex ID - this is important! First remove `extra.symfony.id`
          from your `composer.json` file and after that you just need to run
          `composer symfony:generate-id` command to generate new. Note that
          this will update your `composer.lock` file - so remember to commit
          that.
* [ ] Application basic configuration changes
    * [ ] [.env](../.env) Change `APP_SECRET` value with new one, you can use
          eg. [this](http://nux.net/secret) tool for that. Also remember to
          change that `APP_SECRET` value in [.env.test](../.env.test) and
          [.env.travis](../.env.travis) configuration files.
    * [ ] [application.json](../secrets/application.json) Change file contents
          to match your application configuration - specially you need to
          generate new `JWT_PASSPHRASE` value. Also remember to do those
          changes to [application_test.json](../secrets/application_test.json)
          and [application_travis.json](../secrets/application_travis.json)
          files.
