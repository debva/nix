# NIX PHP Framework

<p align="left">
<a href="https://packagist.org/packages/debva/nix"><img src="https://img.shields.io/packagist/dt/debva/nix" alt="Total Downloads"></a>
<a href="https://github.com/debva/nix"><img src="https://img.shields.io/github/issues/debva/nix" alt="Issues"></a>
<a href="https://github.com/debva/nix"><img src="https://img.shields.io/github/forks/debva/nix" alt="Forks"></a>
<a href="https://github.com/debva/nix"><img src="https://img.shields.io/github/stars/debva/nix" alt="Stars"></a>
<a href="https://github.com/debva/nix"><img src="https://img.shields.io/github/license/debva/nix" alt="License"></a>
</p>

NIX PHP Framework is a lightweight, secure, and versatile PHP framework designed to work seamlessly across various PHP versions. It prioritizes performance, security, and ease of use, making it an ideal choice for developing web applications of all kinds.

## Installation

Install with composer

```bash
composer require debva/nix
```
    
## Usage

.htaccess
```htaccess
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L,QSA]
```

index.php
```php
<?php

require_once(join(DIRECTORY_SEPARATOR, [__DIR__, '..', 'vendor', 'autoload.php']));

$app = new Debva\Nix\App;
$app();

```
