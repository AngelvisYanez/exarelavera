<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\DataCollector;

/**
 * Collects info about the current request
 */
class RequestDataCollector extends DataCollector implements Renderable, AssetProvider
{
    /**
     * @var array[]
     */
    private $blacklist = array(
        '_GET' => array(),
        '_POST' => array(),
        '_COOKIE' => array(),
        '_SESSION' => array(),
    );

    /**
     * @var array[]
     */
    private $data = array();

    /**
     * @return array
     */
    public function collect()
    {
        $vars = array_keys($this->blacklist);
        $data = array();

        try {
            foreach ($vars as $var) {
                if (in_array($var, array('_RESPONSE', '_SERVER')) || ! isset($GLOBALS[$var])) {
                    continue;
                }

                $key = "$" . $var;
                $value = $this->masked($GLOBALS[$var], $var);

                if ($this->isHtmlVarDumperUsed()) {
                    $data[$var] = $this->getVarDumper()->renderVar($value);
                } else {
                    $data[$var] = $this->getDataFormatter()->formatVar($value);
                }
            }
        } catch (\Exception $e) {}

        try {
            $data['_SERVER'] = array();
            $allowedNonHttpKeys = array('REQUEST_METHOD', 'REMOTE_ADDR', 'CONTENT_TYPE', 'CONTENT_LENGTH');
            foreach ($GLOBALS['_SERVER'] as $key => $val) {
                if (strpos($key, 'HTTP_') !== 0 && !in_array($key, $allowedNonHttpKeys)) {
                    continue;
                }
                $data['_SERVER'][ucwords(strtolower(str_replace('_', ' ', preg_replace('/^HTTP_/', '', $key))))] = $val;
            }

            $data['_SERVER'] = $this->masked($data['_SERVER'], '_SERVER');

            if ($this->isHtmlVarDumperUsed()) {
                $data['_SERVER'] = $this->getVarDumper()->renderVar($data['_SERVER']);
            } else {
                $data['_SERVER'] = $this->getDataFormatter()->formatVar($data['_SERVER']);
            }
        } catch (\Exception $e) {}

        try {
            $data['_RESPONSE'] = function_exists('http_response_code')? array('Status Code' => http_response_code()) : array();
            foreach (headers_list() as $val) {
                $val = array_pad(explode(': ', $val, 2), 2, null);
                $data['_RESPONSE'][$val[0]] = $val[1];
            }

            $data['_RESPONSE'] = $this->masked($data['_RESPONSE'], '_RESPONSE');

            if ($this->isHtmlVarDumperUsed()) {
                $data['_RESPONSE'] = $this->getVarDumper()->renderVar($data['_RESPONSE']);
            } else {
                $data['_RESPONSE'] = $this->getDataFormatter()->formatVar($data['_RESPONSE']);
            }
        } catch (\Exception $e) {}

        return array_merge($data, $this->data);
    }

    /**
     * Hide a sensitive value within one of the superglobal arrays.
     *
     * @param string $superGlobalName The name of the superglobal array, e.g. '_GET'
     * @param string|array $key       The key within the superglobal
     * @return void
     */
    public function hideSuperglobalKeys($superGlobalName, $keys)
    {
        if (!is_array($keys)) {
            $keys = array($keys);
        }

        if (!isset($this->blacklist[$superGlobalName])) {
            $this->blacklist[$superGlobalName] = array();
        }

        foreach ($keys as $key) {
            $this->blacklist[$superGlobalName][] = $key;
        }
    }

    /**
     * Checks all values within the given superGlobal array.
     *
     * Blacklisted values will be replaced by a equal length string containing
     * only '*' characters for string values.
     * Non-string values will be replaced with a fixed asterisk count.
     *
     * @param array|\ArrayAccess  $superGlobal     One of the superglobal arrays
     * @param string $superGlobalName The name of the superglobal array, e.g. '_GET'
     *
     * @return array $values without sensitive data
     */
    private function masked($superGlobal, $superGlobalName)
    {
        $blacklisted = isset($this->blacklist[$superGlobalName]) ? $this->blacklist[$superGlobalName] : array();

        $values = $superGlobal;

        foreach ($blacklisted as $key) {
            if (isset($superGlobal[$key])) {
                $values[$key] = str_repeat('*', is_string($superGlobal[$key]) ? strlen($superGlobal[$key]) : 3);
            }
        }

        return $values;
    }

    /**
     * @param string $key
     * @param array $data
     * @return void
     */
    public function addRequestData($key, $data)
    {
        $data = $this->masked($data, $key);

        if ($this->isHtmlVarDumperUsed()) {
            $data = $this->getVarDumper()->renderVar($data);
        } else {
            $data = $this->getDataFormatter()->formatVar($data);
        }

        $this->data['_' . ltrim($key, '_')] = $data;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'request';
    }

    /**
     * @return array
     */
    public function getAssets() {
        return $this->isHtmlVarDumperUsed() ? $this->getVarDumper()->getAssets() : array();
    }

    /**
     * @return array
     */
    public function getWidgets()
    {
        $widget = $this->isHtmlVarDumperUsed()
            ? "PhpDebugBar.Widgets.HtmlVariableListWidget"
            : "PhpDebugBar.Widgets.VariableListWidget";
        return array(
            "request" => array(
                "icon" => "tags",
                "widget" => $widget,
                "map" => "request",
                "default" => "{}"
            )
        );
    }
}
