<?php

class Env {
    private static $values = array();
    private static $loaded = false;

    public static function load() {
        if (self::$loaded) return;

        self::$loaded = true;
        $envFilePath = realpath(__DIR__ . '/../..') . DIRECTORY_SEPARATOR;
        try{
            self::$values = array_merge(self::$values, self::loadFromEnvFile($envFilePath . '.env.example'));
            self::$values = array_merge(self::$values, self::loadFromEnvFile($envFilePath . '.env'));
        } catch (\Exception $e) {
            \Debugbar::addException($e);
        }
    }

    public static function all() {
        self::load();
        return self::$values;
    }

    public static function get($key, $default = null) {
        self::load();
        return isset(self::$values[$key]) ? self::$values[$key] : $default;
    }

    private static function loadFromEnvFile($rutaArchivo) {
        $variables = array();
        if(!file_exists($rutaArchivo))
            return $variables;

        foreach(file($rutaArchivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea){
            $linea = trim($linea);
            if($linea === '' || strpos($linea, '#') === 0 || strpos($linea, ';') === 0 || strpos($linea, '=') === false)continue;
            list($clave, $val) = explode('=', $linea, 2);
            $valor = trim(preg_replace('/^((?:[^"\'#]*(?:(["\'])(?:(?!\2).)*\2[^"\'#]*)*)*)(?:#.*)?$/s', '$1', $val) ?: $val);
            if(
                (substr($valor, 0, 1) === '"' && substr($valor, -1) === '"') ||
                (substr($valor, 0, 1) === "'" && substr($valor, -1) === "'")
            )
                $valor = substr($valor, 1, -1);
            else{
                $valLower = strtolower($valor);
                if(in_array($valLower, array('true', 'false')))
                    $valor = ($valLower === 'true');
                elseif(in_array($valLower, array('', 'null', 'empty')))
                    $valor = null;
                elseif(is_numeric($valLower))
                    $valor = $valor + 0;
            }
            $variables[trim($clave)] = $valor;

        }

        return $variables;
    }
}
