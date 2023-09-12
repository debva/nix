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

- **server/**: This folder houses the server-side components of the project.
    - **api/**: This folder contains the code for the project's API.
    - **middleware/**: This folder contains middleware used in the project.
    - **service/**: This folder contains services used in the project.

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

server/api/user.php
```
<?php

return function() {
    return $this->response('Hello World');
};
```
see result
```
http://localhost/api/v1/user
```

## Available Method
| Method | Parameter | Description 
| -------| --------- | ----------- 
| $this->request() |  
| $this->response() | 
| $this->route() | 
| $this->database() |
| $this->query() | 
| $this->datatable() | 
| $this->telegram() | 
