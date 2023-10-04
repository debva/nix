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

```
composer require debva/nix
```
## Structure

- **public/**: This folder contains files that can be accessed publicly through the web server. It includes the .htaccess file and index.php.

- **app/**: This folder houses the server-side components of the project.
    - **middleware/**: This folder contains middleware used in the project.
    - **routes/**: This folder contains the code for the project's route.
    - **services/**: This folder contains services used in the project.

Feel free to adapt this folder structure template to your project. 
    
## Usage

public/.htaccess
```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L,QSA]
```

public/index.php
```
<?php

require_once(join(DIRECTORY_SEPARATOR, [__DIR__, '..', 'vendor', 'autoload.php']));

$app = new Debva\Nix\App;
$app();
```

Serve from public/index.php
```
php -S 0.0.0.0:80
```

See result
```
http://localhost
```

## Available Method
| Method | Parameter | Description 
| -------| --------- | ----------- 
| $this->request() |  
| $this->validate() | 
| $this->response() | 
| $this->route() | 
| $this->db() |
| $this->query() | 
| $this->transaction() | 
| $this->datatable() | 
| $this->env() | 
| $this->telegram() | 
| $this->loadtime() | 
| $this->dd() | 
