<?php

namespace hakuryo\db\utils;

use Exception;
use JsonException;
use PDO;
use stdClass;

class ConfigParser
{

    const MANDATORY_KEY = ["HOST", "DB", "USER", "PWD", "PORT", "DRIVER"];
    const ALLOWED_DRIVER = ['oci', 'mysql', 'dblib', 'pgsql'];

    public static function parse_config_file($path, $section = null)
    {
        if (!file_exists($path)) {
            throw new Exception("File $path not found or is not readable");
        }
        if (!is_file($path)) {
            throw new Exception("provided path $path is not a file");
        }
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        switch ($ext) {
            case "json":
                if (mime_content_type($path) === "application/json") {
                    return self::validate_config(self::parse_json($path, $section));
                }
                throw new JsonException("Config file is not a json file or the JSON syntaxe is invalide");
            case "ini":
                return self::validate_config(self::parse_ini($path, $section));
            default:
                throw new Exception("Unsupported config file type must be 'json' or 'ini'");
        }
    }

    private static function validate_config(stdClass $config)
    {
        foreach (self::MANDATORY_KEY as $key) {
            if (property_exists($config, $key)) {
                if ($key === 'DRIVER') {
                    if (!in_array(strtolower($config->$key), self::ALLOWED_DRIVER)) {
                        throw new Exception("Wrong 'DRIVER' key value, acceptable values are '" . implode("','", self::ALLOWED_DRIVER) . "'");
                    }
                    if (!in_array(strtolower($config->$key), PDO::getAvailableDrivers())) {
                        throw new Exception("Driver '" . $config->$key . "' is not installed please check your php module with 'php -m' command. Driver found are : '" . implode("','", PDO::getAvailableDrivers()) . "'");
                    }
                }
            } else {
                throw new Exception("You must provide a json file with the followings keys '" . implode("','", self::MANDATORY_KEY) . "'");
            }
        }
        return self::make_pdo_config($config);
    }

    private static function section_exist($config, $section)
    {
        if (is_array($config)) {
            if (!array_key_exists($section, $config)) {
                throw new Exception("The provided section '$section' does not exist");
            }
        }
        if (is_object($config)) {
            if (!property_exists($config, $section)) {
                throw new Exception("The provided section '$section' does not exist");
            }
        }
    }

    private static function parse_ini($path, $section)
    {
        if ($section === null) {
            $raw_conf = parse_ini_file($path);
        } else {
            $raw_conf = parse_ini_file($path, true);
            self::section_exist($raw_conf, $section);
            $raw_conf = parse_ini_file($path, true)[$section];
        }
        return json_decode(json_encode($raw_conf));
    }

    private static function parse_json($path, $section)
    {
        $raw_conf = json_decode(file_get_contents($path), false, 512, JSON_THROW_ON_ERROR);
        if (!$raw_conf) {
            throw new JsonException(json_last_error_msg(), json_last_error());
        }
        if ($section != null) {
            self::section_exist($raw_conf, $section);
            $raw_conf = $raw_conf->$section;
        }
        return $raw_conf;
    }

    private static function make_pdo_config($raw_conf)
    {
        $raw_conf = (object)$raw_conf;
        $config = new stdClass();
        $config->user = $raw_conf->USER;
        $config->pwd = $raw_conf->PWD;
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
        if (property_exists($raw_conf, "THROW_SQL_ERROR")) {
            $config->throw_SQL_error = filter_var($raw_conf->THROW_SQL_ERROR, FILTER_VALIDATE_INT) === 1;
        } else {
            $config->throw_SQL_error = false;
        }
        return $config;
    }
}
