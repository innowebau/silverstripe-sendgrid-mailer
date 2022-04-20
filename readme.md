# Silverstripe Sendgrid Mailer

[![Version](http://img.shields.io/packagist/v/innoweb/silverstripe-sendgrid-mailer.svg?style=flat-square)](https://packagist.org/packages/innoweb/silverstripe-sendgrid-mailer)
[![License](http://img.shields.io/packagist/l/innoweb/silverstripe-sendgrid-mailer.svg?style=flat-square)](license.md)

## Overview

A drop-in solution to send all Silverstripe emails through SendGrid.

This module is only available for Silverstripe 3. For Silverstripe 4, please use [Uncle Cheese's module](https://github.com/unclecheese/silverstripe-sendgrid-mailer).

## Requirements

* Silverstripe Framework 3.7+
* SendGrid 7.11+

## Installation

Install the module using composer:
```
composer require innoweb/silverstripe-sendgrid-mailer dev-master
```
and run dev/build.

## Configuration

You need to define your SendGrid API key in your config:

```
Innoweb\SendGrid\SendGridMailer:
  api_key: 'YOUR API KEY'
```

## License

BSD 3-Clause License, see [License](license.md)
