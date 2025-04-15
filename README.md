# Fennel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nickwelsh/fennel.svg?style=flat-square)](https://packagist.org/packages/nickwelsh/fennel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/nickwelsh/fennel/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/nickwelsh/fennel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/nickwelsh/fennel/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/nickwelsh/fennel/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/nickwelsh/fennel.svg?style=flat-square)](https://packagist.org/packages/nickwelsh/fennel)

A (nearly) feature complete, drop-in replacement for Cloudflare Images

## Installation

You can install the package via composer:

```bash
composer require nickwelsh/fennel
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="fennel-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="fennel-views"
```

## Usage

Use as a blade component:

```blade
<x-fennel-image src="beach.jpg" width="500" :format="\nickwelsh\Fennel\Enums\ImageFormat::AVIF" />
```
This will generate the following HTML:
```html
<img src="/images/beach.jpg/width=500,format=avif" alt="" width="500">
```

Use the `Fennel` facade to generate a URL:
```php
Fennel::fromPath('beach.jpg')->width(500)->format(ImageFormat::AVIF)->getUrl();
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Nick Welsh](https://github.com/nickwelsh)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
