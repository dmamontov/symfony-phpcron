[![Latest Stable Version](https://poser.pugx.org/dmamontov/symfony-phpcron/v/stable.svg)](https://packagist.org/packages/dmamontov/symfony-phpcron)
[![License](https://poser.pugx.org/dmamontov/symfony-phpcron/license.svg)](https://packagist.org/packages/dmamontov/symfony-phpcron)

Symphony PHPCron
================

PHPCron is a daemon to run tasks scheduled cron written in php, works similar to crontab

## Requirements
* PHP version ~5.3.3.
* Module installed "pcntl" and "posix".
* All functions "pcntl" and "posix" removed from the directive "disable_functions".
* Symphony Console ~2.6
* Symphony Process ~2.6
* Symphony FileSystem ~2.6
* Symphony Finder ~2.6

## Installation

1) Install [composer](https://getcomposer.org/download/)

2) Follow in the project folder:
```bash
composer require dmamontov/symfony-phpcron ~2.0.0
```

In config `composer.json` your project will be added to the library `dmamontov/symfony-phpcron`, who settled in the folder `vendor/`. In the absence of a config file or folder with vendors they will be created.

If before your project is not used `composer`, connect the startup file vendors. To do this, enter the code in the project:
```php
require 'path/to/vendor/autoload.php';
```

### Valid parameters
* `execute` `[-f]` `[-d]`
* `cancel` `[-f]`
* `import`
* `status`
* `help`

## Example of work
```php
<?
require_once 'vendor/autoload.php';
use Slobel\PHPCron\Command\Application;

$cron = new Application();
$cron->run();
?>
```
