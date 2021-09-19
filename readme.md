## Patch Note

### 1.3.0
- Refectoring ConfigParser
  - Add support for `pgsl` and `dblib` drivers
  - Now checking if provided driver is installed with PDO::getAvailableDrivers function

### 1.2.1
- Allowed "Truncate" keyword in modify request.
### 1.2.0

- Add $classname param to search and get function. If set, these function will return database line as $classname object. (POD::FETCH_CLASS)

### 1.1.0 (Breaking changes)

- Remove $assoc variable from fucntion **search**,**get**,**modify**. Theses function now detect automatically if provided array is associative or not.

## Install

> composer require hakuryo/database-client:^1

## Dependencies

### Mandatory

- PHP >= 7.x 

### Optionnal

> Only If you want to use ConnectionDB to connect to an ORACLE database

- Oracle Instantclient
- PHP PDO_OCI

## Features
- Parsing client config from INI and JSON file

## Usage & exemples

### Exemple INI file

```INI
[mysql]
HOST = "localhost"
DB = mydb
USER = "root"
PWD = "mypass"
PORT = 1234
DRIVER = "mysql" ;Accepted Values are oci,mysql

[oracle]
HOST = "localhost"
DB = mydb
USER = "root"
PWD = "mypass"
PORT = 1234
DRIVER = "oci" ;Accepted Values are oci,mysql
```

### Exemple JSON file

```JSON
{
    "db": {
        "DB": "mydb",
        "HOST": "localhost",
        "USER": "root",
        "PWD": "mypass",
        "PORT": 3306,
        "DRIVER": "mysql"
    }
}
```

### ConnectionDB usage

```PHP

require "./vendor/autoload.php";

use hakuryo\db\ConnectionDB;
//Connection to mysql
$db = ConnectionDB::from_file('config.ini', 'mysql');
//Usage of anonnymous params
$rq = "SELECT * FROM users";
// search function is for multiple result
print_r($db->search($rq, [1234]));
$db =null;

//Connection to oracle
$db = ConnectionDB::from_file('config.ini', 'oracle');
$rq = "SELECT firstname FROM users WHERE id = :id";
//Usage of named params
// get function return the first line of the result
$result $db->get($rq, ["id"=>1234]);

// Check if result is relevant
if(property_exist($result,'id')){
    print_r($result);
}
$db =null;

//Connection with a config.ini without section
$db = ConnectionDB::from_file('config_without_section.ini');
$rq = "INSERT INTO users (firstname,lastname) VALUES (:fname,:lname)";
//Modify is use to perform update, insert or delete operation
print_r($db->modify($rq, ["fname"=>"Bob","lname"=>"Moran"]));
$db =null;

```