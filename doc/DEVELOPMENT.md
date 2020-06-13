# What is this?

This document contains basic information how you _should_ development this
application.

## Table of Contents

* [What is this?](#what-is-this)
  * [Table of Contents](#table-of-contents)
  * [Development](#development)
    * [IDE](#ide)
    * [PHP Code Sniffer](#php-code-sniffer)
    * [Database changes](#database-changes)

## Development

* [PSR-1](https://www.php-fig.org/psr/psr-1/)
* [PSR-2](https://www.php-fig.org/psr/psr-2/)
* [Coding Standards](http://symfony.com/doc/current/contributing/code/standards.html)

### IDE

I highly recommend that you use "proper"
[IDE](https://en.wikipedia.org/wiki/Integrated_development_environment)
to development your application. Below is short list of some popular IDEs that
you could use.

* [PhpStorm](https://www.jetbrains.com/phpstorm/)
* [NetBeans](https://netbeans.org/)
* [Sublime Text](https://www.sublimetext.com/)
* [Visual Studio Code](https://code.visualstudio.com/)

Just choose one which is the best for you.
Also note that project contains `.idea` folder that holds default settings for
PHPStorm.

### PHP Code Sniffer

It's highly recommended that you use this tool while doing actual development
to application. PHP Code Sniffer is added to project ```dev``` dependencies, so
all you need to do is just configure it to your favorite IDE. So the `phpcs`
command is available via following example command.

```bash
./vendor/bin/phpcs -i
```

If you're using [PhpStorm](https://www.jetbrains.com/phpstorm/) this following link
will help you to get things rolling.

* [Using PHP Code Sniffer Tool](https://www.jetbrains.com/help/phpstorm/using-php-code-sniffer.html)

### Database changes

Migration files contain all necessary database changes
to get application running with its database structure. You can migrate
these changes to your database with following command:

```bash
./bin/console doctrine:migrations:migrate
```

After that you can start to modify or delete existing entities or create your
own ones. Easiest way to make this all work is to follow below workflow:

1. Make your changes (create, delete, modify) to entities in `/src/Entity/` folder
1. Run `diff` command to create new migration file
1. Run `migrate` command to make actual changes to your database
1. Run `validate` command to validate your mappings and actual database structure

Those commands you can run with `./bin/console doctrine:migrations:<command>`.

With this workflow you get easy approach to generic database changes on your
application. And you don't need to make any migrations files by hand (just let
Doctrine handle those). Although remember to really take a closer look of those
generated migration files to make sure that those doesn't contain anything that
you really don't want.

---

[Back to resources index](README.md) - [Back to main README.md](../README.md)
