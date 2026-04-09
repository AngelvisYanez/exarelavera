<?php

class Env {
    private static $values = array();
    private static $loaded = false;

    public static function load() {
        if (self::$loaded) return;

        self::$loaded = true;
        $envFilePath = realpath(__DIR__ . '/../..') . DIRECTORY_SEPARATOR; // Ajusta la ruta a tu directorio root
        try{
            self::$values = array_merge(self::$values, self::loadFromEnvFile($envFilePath . '.env.example'));
            self::$values = array_merge(self::$values, self::loadFromEnvFile($envFilePath . '.env'));
        } catch (\Exception $e) {
            \Debugbar::addException($e);
        }
    }

    public static function all() {
        self::load(); // Asegúrate de que los valores estén cargados
        return self::$values;
    }

    public static function get($key, $default = null) {
        self::load(); // Asegúrate de que los valores estén cargados
        // return self::$values[$key] ?? $default; // PHP 5.6 no tiene ??, usar ternario
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
            if( // Verifica si el valor está entre comillas (simples o dobles)
                (substr($valor, 0, 1) === '"' && substr($valor, -1) === '"') ||
                (substr($valor, 0, 1) === "'" && substr($valor, -1) === "'")
            )
                $valor = substr($valor, 1, -1); // quitar comillas
            else{
                $valLower = strtolower($valor);
                if(in_array($valLower, array('true', 'false')))
                    $valor = ($valLower === 'true'); // soporte de bool
                elseif(in_array($valLower, array('', 'null', 'empty')))
                    $valor = null; // vacio se interpreta como null
                elseif(is_numeric($valLower))
                    $valor = $valor + 0; // convierte a int o float
            }
            $variables[trim($clave)] = $valor;
        }

        return $variables;
    }
}
