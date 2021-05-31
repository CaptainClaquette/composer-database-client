# INSTALLATION PHP OCI8 PHP_PDO_OCI <a name="TOP"></a>

* [PHP](#PHP)
* [OCI8](#OCI8)
  * PRE-REQUIS
  * INSTALLATION OCI8
    * RECUPERATION DES SOURCES OCI8
    * VERIFICATION DES SOURCES OCI8
    * Compilation de l'extension OCI8
    * Configuration de systeme pour OCI8
    * Configuration de PHP-CLI pour OCI8
    * Configuration de PHP-APACHE pour OCI8
  * Problèmes possible
    * libaio.so.1: cannot open shared object file: No such file or directory
  * Debugging
* [PHP_PDO_OCI](#PHP_PDO_OCI)
  * Compilation de l'extension PHP_PDO_OCI
  * Configuration PHP_PDO_OCI
* Utilisation de PHP_PDO_OCI

## <a name="PHP"></a>PHP
You can install php package using the following command

```BASH
apt install php
```

> Please take note of your php version, it will be use in the following steps.

## <a name="OCI8"></a>OCI8

### REQUIREMENTS

In order to install OCI8 you must install the following packages :

- php-pear
- php-dev

```BASH
apt install php-pear php-dev
```

### OCI8 installation

#### GET OCI8 sources

1. Download Basic and SDK pack zip file of InstantClient : https://www.oracle.com/fr/database/technologies/instant-client/linux-x86-64-downloads.html
2. Create the directory /opt/oci8/
3. unzip the SDK and Basic pack into /opt/oci8/

#### Checking OCI8 sources

In `/opt/oci8/` check if the following symlinks are present :

- libclntsh.so -> libclntsh.so.XX.X
- libocci.so -> libocci.so.XX.X

If you don't find the links you could create them with the following commands :

- ln -s libclntsh.so.XX.X libclntsh.so
- ln -s libocci.so.XX.X libocci.so

#### OCI8 compilation

To compile OCI8 you must execute the following command :

**For php 8+**
```BASH
pecl install oci8
```
**For php 7.4**
```BASH
pecl install oci8-2.2.0
```

When PECL ask for the instantClient path provide the path where you've unzip the instantClient Basic and SDK pack:

>'instantclient,/opt/oci8/'

You should have the following result

<img src="data:image/png;base64,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"/>

> Please take note of the installation path `/usr/lib/php/XXXXXXXX/` it will be used for the configuration steps.

#### System configuration for OCI8

Use the following command to add oracle instantclient lib to your system.

> echo /opt/oci8/ > /etc/ld.so.conf.d/oracle-instantclient.conf

Then execute

> ldconfig

In `/etc/profile`, add the following line

> export LD_LIBRARY_PATH=/opt/oci8/

#### PHP-CLI configuration for OCI8

In `/etc/php/X.X/cli/php.ini`  

Search for `Path and Directories` section

```INI
;;;;;;;;;;;;;;;;;;;;;;;;;
; Paths and Directories ;
;;;;;;;;;;;;;;;;;;;;;;;;;

; UNIX: "/path1:/path2"
;include_path = ".:/usr/share/php"
;
; Windows: "\path1;\path2"
;include_path = ".;c:\php\includes"
;
; PHP's default setting for include_path is ".;/path/to/php/pear"
; http://php.net/include-path
...
```

Add a line with the installation path provided in step OCI8 compilation: 

> extension_dir = "/usr/lib/php/XXXXXXX" 

Search for extensions declaration location :

```INI
;;;;;;;;;;;;;;;;;;;;;;
; Dynamic Extensions ;
;;;;;;;;;;;;;;;;;;;;;;

; If you wish to have an extension loaded automatically, use the following
; syntax:
;
;   extension=modulename.extension
;
; For example, on Windows:
;
;   extension=msql.dll
;
; ... or under UNIX:
;
;   extension=msql.so
;
; ... or with a path:
;
;   extension=/path/to/extension/msql.so
;
; If you only provide the name of the extension, PHP will look for it in its
; default extension directory.
;
; Windows Extensions
; Note that ODBC support is built in, so no dll is needed for it.
; Note that many DLL files are located in the extensions/ (PHP 4) ext/ (PHP 5+)
; extension folders as well as the separate PECL DLL download (PHP 5+).
; Be sure to appropriately set the extension_dir directive.
;
;extension=php_bz2.dll
;extension=php_curl.dll
;extension=php_fileinfo.dll
...
```

Add the following line :

> extension=oci8.so

#### PHP-APACHE configuration for OCI8

In `/etc/php/X.X/apache2/php.ini` follow the same step describ in PHP-CLI configuration for OCI8

> You must restart apache

### Troubleshooting

#### libaio.so.1: cannot open shared object file: No such file or directory

If you get the error you must install the following package

```BASH
apt-get install libaio1 libaio-dev
```

### Debugging

You could get the requirement of OCI8 using the following command :

```BASH
ldd /usr/lib/php/XXXXXXXXX/oci8.so
```

If a lib is flag `not found` search how install it with `apt`.

## <a name="PHP_PDO_OCI"></a>PHP_PDO_OCI

Download your PHP version source files on https://github.com/php/php-src.
You must take the branch conrresponding to your php version
> To find your php version use `php -v`

Place the dowloaded file in `/tmp` and unzip it.

go into `/tmp/php-src-PHP-X.X.XX/ext/pdo_oci/` directory

### PHP_PDO_OCI compilation

To compile PDO_OCI use the following command :

> phpize  
> ./configure --with-pdo-oci=instantclient,/opt/oci8/  
> make && make install  

### PHP_PDO_OCI configuration

For console usage of pdo_oci edit `/etc/php/X.X/cli/php.ini`  
For apache2 usage of pdo_oci edit `/etc/php/X.X/apache2/php.ini`  
For console and apache2 usage of pdo_oci edit `/etc/php/X.X/apache2/php.ini` and `/etc/php/X.X/cli/php.ini`  

Search for extensions declaration location and add the following line :

> extension=pdo_oci

```INI
;extension=pdo_mysql
;extension=pdo_odbc
;extension=pdo_pgsql
;extension=pdo_sqlite
;extension=pgsql
;extension=shmop
extension=pdo_oci
extension=oci8.so
```

## PHP_PDO_OCI usage

```PHP
$db_username = "login";
$db_password = "password";
$port = 1522;
$db = "BD_NAME";
$host = "database_oracle.domain.com";
// Elaboration de la chaine de connection oracle
$dsn = "oci:dbname=$host:$port/$db";

try {
    $conn = new PDO($dsn, $db_username, $db_password);
    $rq = "SELECT * FROM user";
    $stmt->execute();
    $metas = [];
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    echo($e->getMessage());
}
//close de connection
$conn = null;
