<?php

namespace hakuryo\db;

use PDO;
use PDOStatement;
use Exception;
use hakuryo\db\utils\ConfigParser;

class ConnectionDB extends PDO
{

    const QUERY_TYPE_SEARCH = 1;
    const QUERY_TYPE_MODIFY = 2;

    /**
     * Create a new instance of ConnectionDB.
     * @param type $config_path the location of the ini file
     * @return ConnectionDB
     */
    public function __construct(string $dsn, string $username = NULL, string $passwd = NULL, array $options = NULL)
    {
        parent::__construct($dsn, $username, $passwd, $options);
        if (!$this->is_oci()) {
            $this->query("SET NAMES 'utf8'");
        }
    }

    /**
     * Create a new instance of ConnectionDB from a ini file.
     * The ini file MUST have the following keys : HOST,DB,USER,PWD,PORT
     * @param type $config_path the location of the ini file
     * @return ConnectionDB
     * @throws Exception If the ini file don't provide the mandatory keys "HOST", "DB", "USER", "PWD", "PORT", "DRIVER"
     */
    public static function from_file($path, $section = null): ConnectionDB
    {
        $conf = ConfigParser::parse_config_file($path, $section);
        $con = new ConnectionDB($conf->dsn, $conf->user, $conf->pwd);
        return $con;
    }

    /**
     * Perform a SELECT/SHOW/DESCRIB request and expect multiple results
     * @param string $request the request to execute. You can use preparedQuery placeholder in $request
     * @param array $args An associative/sequential array of argument for the prepared query
     * @return array An array of stdClass object.
     * @throws Exception If the query is not SELECT/SHOW/DESCRIB
     */
    public function search(string $request, array $args = []): array
    {
        $this->check_query_type($request, self::QUERY_TYPE_SEARCH);
        $stmt = $this->prepare($request);
        $this->bind_values($stmt, $args);
        $stmt->execute();
        return $this->is_oci() ? $this->fecth_data($stmt) : $this->cast_data($stmt);
    }

    /**
     * Perform a SELECT/SHOW/DESCRIB request and get the first result
     * @param string $request the request to execute. You can use preparedQuery placeholder in $request
     * @param array $args An associative/sequential array of argument for the prepared query
     * @return stdClass return You must check if result is relevant with property_exists function.
     * @throws Exception If the query is not SELECT/SHOW/DESCRIB
     * @see property_exists
     */
    public function get($request, $args = []): \stdClass
    {
        $result = $this->search($request, $args);
        return count($result) > 0 ? $result[0] : new \stdClass();
    }

    /**
     * Perform a UPDATE/INSERT/DELETE and return the number of affected rows
     * @param string $request the request to execute. You can use preparedQuery placeholder in $request
     * @param array $args An associative/sequential array of argument for the prepared query
     * @return int the number of affected rows
     * @throws Exception If the query is not UPDATE/INSERT/DELETE
     */
    public function modify($request, $args = []): int
    {
        $this->check_query_type($request, self::QUERY_TYPE_MODIFY);
        $stmt = $this->prepare($request);
        $this->bind_values($stmt, $args);
        $stmt->execute();
        return $stmt->rowCount();
    }

    private function is_oci()
    {
        return $this->getAttribute(PDO::ATTR_DRIVER_NAME) === 'oci';
    }

    private function is_assoc(array $array)
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

    private function bind_values(PDOStatement &$stmt, array $args)
    {
        if ($this->is_assoc($args)) {
            foreach ($args as $k => $v) {
                $stmt->bindValue($k, $v);
            }
        } else {
            for ($i = 0; $i < count($args); $i++) {
                $stmt->bindValue($i + 1, $args[$i]);
            }
        }
    }

    private function check_query_type(string $query, int $type)
    {
        $rqType = explode(' ', $query);
        switch ($type) {
            case self::QUERY_TYPE_SEARCH:
                if (preg_match("/insert|delete|update/", strtolower($rqType[0]))) {
                    throw new Exception('The query must be of type : SELECT, DESCRIBE or SHOW');
                }
                break;
            case self::QUERY_TYPE_MODIFY:
                if (!preg_match("/insert|delete|update/", strtolower($rqType[0]))) {
                    throw new Exception('The query must be of type : UPDATE, DELETE or INSERT');
                }
                break;
        }
    }

    private function cast_data(PDOStatement $stmt)
    {
        $result = [];
        $metas = [];
        foreach (range(0, $stmt->columnCount() - 1) as $column_index) {
            $metas[] = $stmt->getColumnMeta($column_index);
        }
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $obj = new \stdClass();
            foreach ($row as $index => $value) {
                $meta = $metas[$index];
                $name = $meta['name'];
                $obj->$name = $this->get_casted_value($meta, $value);
            }
            $result[] = $obj;
        }
        return $result;
    }

    private function fecth_data(PDOStatement $stmt)
    {
        return json_decode(json_encode($stmt->fetchAll(PDO::FETCH_ASSOC)));
    }

    private function get_casted_value($meta, $value)
    {
        if (!key_exists('native_type', $meta)) {
            return $value;
        }
        switch ($meta['native_type']) {
            case 'LONG':
            case 'INT':
                return intval($value);
            case 'TIMESTAMP':
                return \DateTime::createFromFormat("Y-m-d H:i:s", $value)->getTimestamp();
            case 'TINY':
                return $meta['len'] > 1 ? intval($value) : intval($value) == 1;
            default:
                return $value;
        }
    }
}
