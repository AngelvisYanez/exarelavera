<?php
/**
 * Formato vertical de JSON compatible con PHP 5.3 (sin JSON_PRETTY_PRINT).
 */
if (!function_exists('dashboard_json_pretty')) {
function dashboard_json_pretty($compact) {
    $out = '';
    $indent = 0;
    $inString = false;
    $escape = false;
    $n = strlen($compact);
    $pad = '    ';
    for ($i = 0; $i < $n; $i++) {
        $ch = $compact[$i];
        if ($escape) {
            $out .= $ch;
            $escape = false;
            continue;
        }
        if ($ch === '\\' && $inString) {
            $out .= $ch;
            $escape = true;
            continue;
        }
        if ($ch === '"') {
            $inString = !$inString;
            $out .= $ch;
            continue;
        }
        if ($inString) {
            $out .= $ch;
            continue;
        }
        if (ctype_space($ch)) {
            continue;
        }
        if ($ch === '{' || $ch === '[') {
            $out .= $ch . "\n" . str_repeat($pad, ++$indent);
        } elseif ($ch === '}' || $ch === ']') {
            $out .= "\n" . str_repeat($pad, --$indent) . $ch;
        } elseif ($ch === ',') {
            $out .= $ch . "\n" . str_repeat($pad, $indent);
        } elseif ($ch === ':') {
            $out .= ': ';
        } else {
            $out .= $ch;
        }
    }
    return $out;
}
}

if (!function_exists('dashboard_json_encode_save')) {
function dashboard_json_encode_save($input) {
    $compact = json_encode($input);
    if ($compact === false) {
        return false;
    }
    if (defined('JSON_PRETTY_PRINT')) {
        $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;
        if (defined('JSON_UNESCAPED_SLASHES')) {
            $flags |= JSON_UNESCAPED_SLASHES;
        }
        $pretty = json_encode($input, $flags);
        return ($pretty !== false) ? $pretty : $compact;
    }
    return dashboard_json_pretty($compact);
}
}
