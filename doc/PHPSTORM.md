# What is this?

This document contains information how you can setup your [PhpStorm](https://www.jetbrains.com/phpstorm/)
for this application _"correct"_ way.

## Table of Contents

* [What is this?](#what-is-this)
   * [Table of Contents](#table-of-contents)
   * [Setup](#setup)
      * [CLI Interpreter](#cli-interpreter)
      * [Server](#server)
      * [Test Frameworks](#test-frameworks)
   * [External links / resources](#external-links--resources)

## Setup

### CLI Interpreter

First thing that you need to do is select correct CLI interpreter for your 
PhpStorm. Selection should be available on `Settings -> Languages & Frameworks -> PHP`
section.

Just choose the `Docker-PHP` from dropdown, if that does not exist there you
need to click that `...` and follow the instructions in [External links / resources](#external-links--resources)
section.

![Path mappings](images/phpstorm_01.png)

### Server

Next thing to configure is used PHP servers. This you can do in
`Settings -> Languages & Frameworks -> PHP -> Servers` - purpose of this is to
configure your PhpStorm to know how your local files are mapped inside that
docker container.

![Path mappings](images/phpstorm_02.png)

### Test Frameworks

Application itself contains quite lot of tests as you know from that [testing](TESTING.md)
documentation. To get support to run tests directly from your IDE you need to
do following configuration in `Settings -> Languages & Frameworks -> PHP -> Test Frameworks`.

By default settings should be set correctly but just ensure that  those are set
as in image below.

![Path mappings](images/phpstorm_03.png)

## External links / resources

* [Configuring Remote PHP Interpreters](https://www.jetbrains.com/help/phpstorm/configuring-remote-interpreters.html)
