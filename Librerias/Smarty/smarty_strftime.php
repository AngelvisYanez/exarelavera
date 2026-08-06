<?php
function smarty_strftime($format, $timestamp = null)
{
    if ($timestamp === null) {
        $timestamp = time();
    }
    if (function_exists('strftime')) {
        return @strftime($format, $timestamp);
    }
    $map = [
        '%a' => 'D', '%A' => 'l', '%d' => 'd', '%e' => 'j',
        '%b' => 'M', '%B' => 'F', '%m' => 'm', '%y' => 'y', '%Y' => 'Y',
        '%H' => 'H', '%I' => 'h', '%M' => 'i', '%S' => 's', '%p' => 'A',
        '%r' => 'h:i:s A', '%R' => 'H:i', '%T' => 'H:i:s',
    ];
    $dateFormat = str_replace(array_keys($map), array_values($map), $format);
    return date($dateFormat, $timestamp);
}
