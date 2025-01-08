<?php

namespace hakuryo\db;

use hakuryo\ConfigParser\ConfigParser;
use PDO;
use PDOStatement;
use Exception;
use InvalidArgumentException;

class ConnectionDB extends PDO
{
    public int $lastRowCount = -1;
    const MANDATORY_KEY = ["HOST", "DB", "USER", "PWD", "PORT", "DRIVER"];
    const ALLOWED_DRIVER = ['oci', 'mysql', 'dblib', 'pgsql'];

    /**
     * @param string $dsn connection string
     * @param string|NULL $username User login form your database
     * @param string|NULL $passwd User password for your database
     * @param array|NULL $options PDO options
     */
    public function __construct(string $dsn, string $username = NULL, string $passwd = NULL, array $options = NULL)
    {
        parent::__construct($dsn, $username, $passwd, $options);
    }

    /**
     * Create a new instance of ConnectionDB from a ini file.
     * The ini file MUST have the following keys : HOST,DB,USER,PWD,PORT
     * @param string $path the location of the ini file
     * @return ConnectionDB
     * @throws Exception If the ini file don't provide the mandatory keys "HOST", "DB", "USER", "PWD", "PORT", "DRIVER"
     */
    public static function fromFile(string $path, $section = null): ConnectionDB
    {
        $rawConf = ConfigParser::parse($path, $section, self::MANDATORY_KEY);
        self::verifyDriver($rawConf);
        $conf = self::makePDOConfig($rawConf);
        $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
        if ($conf->timeout) {
            $options[] = [PDO::ATTR_TIMEOUT => intval($conf->timeout)];
        }
        return new ConnectionDB($conf->dsn, $conf->user, $conf->pwd, $options);
    }

    /**
     * Perform a request and expect multiple results
     * @param string $request the request to execute. You can use preparedQuery placeholder in $request
     * @param array $args An associative/sequential array of argument for the prepared query
     * @param string $classname a className for pdo to return. Default stdClass
     * @param callable|null $callback a function to call on each line. Default null
     * @param string|null $trackBy replace the auto index key of the array by the value of the key for the corresponding line.
     * Tracking by "name" for example while generate an array like so :
     * * array['value_of_name for entry'] = "corresponding entry"
     * @return array|null return the result as an array of $classname object or null if no result.
     * @throws \PDOException
     * */
    public function search(string $request, array $args = [], string $classname = "stdClass", callable $callback = null, string $trackBy = null): array|null
    {
        $stmt = $this->prepare($request);
        $this->bindValues($stmt, $args);
        $result = $stmt->execute();
        if ($result) {
            $stmt->setFetchMode(PDO::FETCH_CLASS, $classname);
            return $this->fetchResults($stmt, $callback, $trackBy);
        }
        return null;
    }

    /**
     * Perform request and retrieve the first result;
     * @param string $request the request to execute. You can use preparedQuery placeholder in $request
     * @param array $args An associative/sequential array of argument for the prepared query
     * @param string $classname a className for pdo to return. Default stdClass
     * @param callable|null $callback a function to call on each line. Default null
     * @return $classname|null return the result as object of className or null if no result.
     * @throws \PDOException
     */
    public function get($request, $args = [], string $classname = "stdClass", callable $callback = null)
    {
        $stmt = $this->prepare($request);
        $this->bindValues($stmt, $args);
        if ($stmt->execute()) {
            $stmt->setFetchMode(PDO::FETCH_CLASS, $classname);
            $line = $stmt->fetch();
            if ($line !== false) {
                if ($callback !== null) {
                    call_user_func($callback, $line);
                }
                return $line;
            }
        }
        return null;
    }

    /**
     * Perform a procedure call
     * @param string $request the procedure call to execute. You can use preparedQuery placeholder in $request
     * @param array $args An associative/sequential array of argument for the prepared query
     * @param int $return_type If > 0 return rowCount, else return array of stdclass
     * @return mixed
     * @throws Exception
     */
    public function call(string $request, array $args = [], string $classname = "stdClass", callable $callback = null): mixed
    {
        $stmt = $this->prepare($request);
        $this->bindValues($stmt, $args);
        $result = $stmt->execute();
        if ($result) {
            $stmt->setFetchMode(PDO::FETCH_CLASS, $classname);
            $this->lastRowCount = $stmt->rowCount();
            return $this->fetchResults($stmt, $callback);
        }
        return null;
    }


    /**
     * Perform a UPDATE/INSERT/DELETE and return the number of affected rows
     * @param string $request the request to execute. You can use preparedQuery placeholder in $request
     * @param array $args An associative/sequential array of argument for the prepared query
     * @return int the number of affected rows
     * @throws Exception If the query is not UPDATE/INSERT/DELETE
     */
    public function modify(string $request, array $args = []): int
    {
        $stmt = $this->prepare($request);
        $this->bindValues($stmt, $args);
        $stmt->execute();
        $this->lastRowCount = $stmt->rowCount();
        return $stmt->rowCount();
    }

    private function isDriver($driver): bool
    {
        return $this->getAttribute(PDO::ATTR_DRIVER_NAME) === $driver;
    }

    private function isAssoc(array $array): bool
    {
        if (count($array) === 0) {
            return false;
        }
        foreach ($array as $k => $value) {
            if (!is_string($k)) {
                return false;
            }
        }
        return true;
    }

    private function bindValues(PDOStatement &$stmt, array $args): void
    {
        if ($this->isAssoc($args)) {
            foreach ($args as $k => $v) {
                $stmt->bindValue($k, $v, $this->getSQLType($v));
            }
        } else {
            for ($i = 0; $i < count($args); $i++) {
                $stmt->bindValue($i + 1, $args[$i], $this->getSQLType($args[$i]));
            }
        }
    }

    private function getSQLType($value): int
    {
        switch (gettype($value)) {
            case "boolean":
                return PDO::PARAM_BOOL;
            case "integer":
                return PDO::PARAM_INT;
            case "NULL":
                return PDO::PARAM_NULL;
            default:
                return PDO::PARAM_STR;
        }
    }

    private function fetchResults(PDOStatement $stmt, callable $callback = null, string $trackBy = null): array
    {
        $result = [];
        while ($line = $stmt->fetch()) {
            if ($callback != null) {
                call_user_func($callback, $line);
            }
            if ($trackBy !== null) {
                try {
                    $reflect = new \ReflectionClass($line);
                    if ($reflect->getShortName() !== \stdClass::class) {
                        $track = $reflect->getProperty($trackBy)->getValue($line);
                        $result[$track] = $line;
                    } else {
                        $result[$line->$trackBy] = $line;
                    }
                } catch (\ReflectionException $e) {
                    error_reporting($e->getMessage());
                    $result[] = $line;
                }
            } else {
                $result[] = $line;
            }

        }
        return $result;
    }

    private static function verifyDriver(\stdClass $config)
    {
        if (!in_array(strtolower($config->DRIVER), self::ALLOWED_DRIVER)) {
            throw new Exception("Wrong 'DRIVER' key value, acceptable values are '" . implode("','", self::ALLOWED_DRIVER) . "'");
        }
        if (!in_array(strtolower($config->DRIVER), PDO::getAvailableDrivers())) {
            throw new Exception("Driver '" . $config->DRIVER . "' is not installed please check your php module with 'php -m' command. Driver found are : '" . implode("','", PDO::getAvailableDrivers()) . "'");
        }
    }

    private static function makePDOConfig($raw_conf)
    {
        $raw_conf = (object)$raw_conf;
        $config = new \stdClass();
        $config->user = $raw_conf->USER;
        $config->pwd = $raw_conf->PWD;
        if (property_exists($raw_conf, 'TIMEOUT')) {
            $config->timeout = $raw_conf->TIMEOUT;
        }
        switch ($raw_conf->DRIVER) {
            case 'mysql':
            case 'pgsql':
                $config->dsn = "$raw_conf->DRIVER:host=" . $raw_conf->HOST . ";dbname=" . $raw_conf->DB . ";port=" . intval($raw_conf->PORT);
                break;
            case 'dblib':
            case 'sybase':
                $config->dsn = "dblib:host=$raw_conf->HOST:" . intval($raw_conf->PORT) . ";dbname=" . $raw_conf->DB;

                break;
            case 'oci':
                $config->dsn = "oci:dbname=" . $raw_conf->HOST . ":" . intval($raw_conf->PORT) . "/" . $raw_conf->DB;
                break;
        }
        if (property_exists($raw_conf, 'CHARSET')) {
            $config->dsn .= ";charset=$raw_conf->CHARSET";
        }
        return $config;
    }
}
