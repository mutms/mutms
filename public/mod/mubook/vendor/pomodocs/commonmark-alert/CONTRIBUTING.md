# Contributing

First of all, many thanks to spend your time on this library!

> [!WARNING]
> To contribute to this library, you should have installed on your computer:
> - PHP >= 8.3 with _mbstring_ extension
> - [Phive](https://phar.io/) phar manager

To avoid conflicts between dependencies, we use Phive to install the development tools (PhpStan and Php-cs-fixer) as phar archives, into the `tools` directory.

## Workflow

1. Fork [pomodocs/commonmark-alert](https://github.com/pomodocs/commonmark-alert) repository.
2. Install all the dependencies via Composer: `composer install`.
3. Install the development tools in `tools` directory via Phive: `phive install`.
4. Run the test suite by `composer test` command and fix all red tests.
5. Run static analysis tool by `composer analytics` command and fix all errors.
6. Fix the coding standard by running `composer cs:fix`.

> [!TIP]
> We provide a __check__ command for all the previously described actions:
> run `composer check` before submitting a pull request.

## Running the Test Suite

While developing, the test part is very important: if you apply a patch to the existing code, the test suite must run without errors or failures and if you add a new functionality, no one will consider it without tests.

Our test tool is [PhpUnit](https://phpunit.de/) and we provide a script to launch it:

```bash
composer test
```

## Code Coverage

We provide two commands to generate the code coverage report in _html_ or _xml_ format:

-  `composer coverage:html` command generates a code coverage report in _html_ format, into the directory `coverage/`
-  `composer coverage:clover` generates the report in _xml_ format, into `clover.xml` file.


## Static Analysis Tool

We use [PhpStan](https://phpstan.org/) as static analysis tool.
To launch it, run the following command:

```bash
composer analytics
```


## Coding Standard

We ship our script to easily fix coding standard errors, via [php-cs-fixer](https://cs.symfony.com/) tool.
To fix coding standard errors just run:

```bash
composer cs:fix
```

and to show the errors without fixing them, run:

```bash
composer cs:check
```
