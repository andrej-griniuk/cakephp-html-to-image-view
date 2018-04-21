[![Build Status](https://img.shields.io/travis/andrej-griniuk/cakephp-html-to-image-view/master.svg?style=flat-square)](https://travis-ci.org/andrej-griniuk/cakephp-html-to-image-view)
[![Coverage Status](https://img.shields.io/coveralls/andrej-griniuk/cakephp-html-to-image-view.svg?style=flat-square)](https://coveralls.io/r/andrej-griniuk/cakephp-html-to-image-view?branch=master)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

# HtmlToImageView plugin for CakePHP

This plugin renders html views as image (jpg or png) using `wkhtmltoimage` command line utility from [WkHtmlToPdf](https://wkhtmltopdf.org/) package.

## Requirements

- CakePHP 3.5+
- [wkhtmltoimage](https://wkhtmltopdf.org/)

## Installation

You can install this plugin into your CakePHP application using [Composer][composer].

```bash
composer require andrej-griniuk/cakephp-html-to-image-view
```

## Usage

First of all you need to load the plugin in your bootstrap.php

```php
Plugin::load('HtmlToImageView', ['bootstrap' => true, 'routes' => true]);
```

This will enable `jpeg` and `png` extensions on any route. Alternatively you could load the plugin without `'routes' => true` and only enable extensions on the routes you would like.

Layout path and templates sub dir are `img`, e.g. you'll need to create `src/Template/Layout/img/defaut.ctp` for your image views and and image view template would be, for example, `src/Template/Events/img/view.ctp`. Then simply call, for example, `http://localhost/events/view.jpg` to render your view as image.

Default path to `wkhtmltoimage` binary is `/usr/bin/wkhtmltoimage`. You can change it by setting `HtmlToImageView.binary` configuration variable:

```php
Configure::write('HtmlToImageView.binary', '/another/path/to/wkhtmltoimage');
```

You can pass some options to `wkhtmltoimage` from your view via `$this->viewOptions(['imageOptions' => [...])`. List of available options:
 - **crop-h** - Set height for cropping
 - **crop-w** - Set width for cropping
 - **crop-x** - Set x coordinate for cropping
 - **crop-y** - Set y coordinate for cropping
 - **format** - Output file format (jpg/png)
 - **width** - Set screen width, note that this is used only as a guide line (default 1024)
 - **height** - Set screen height (default is calculated from page content)
 - **zoom** - Zoom level
 - **quality** - Output image quality (between 0 and 100) (default 94)
 
For example:

```php
$this->viewOptions([
    'imageOptions' => [
        'width' => 250,
        'zoom' => 2
    ],
]);
```

See the full documentation and installation instruction for `wkhtmltoimage` at the [project website](https://wkhtmltopdf.org/)

## Bugs & Feedback

https://github.com/andrej-griniuk/cakephp-html-to-image-view/issues

## License

Copyright (c) 2018, [Andrej Griniuk][andrej-griniuk] and licensed under [The MIT License][mit].

[cakephp]:http://cakephp.org
[composer]:http://getcomposer.org
[mit]:http://www.opensource.org/licenses/mit-license.php
[andrej-griniuk]:https://github.com/andrej-griniuk
