<?php

/*
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Monolog\Formatter;

use Monolog\Utils;

/**
 * Formats incoming records into a one-line string
 *
 * This is especially useful for logging to files
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Christophe Coevoet <stof@notk.org>
 */
class LineFormatter extends NormalizerFormatter
{
    const SIMPLE_FORMAT = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";

    protected $format;
    protected $allowInlineLineBreaks;
    protected $ignoreEmptyContextAndExtra;
    protected $includeStacktraces;
    protected $indentStacktraces = '';
    protected $stacktracesParser = null;
    protected $basePath = '';

    /**
     * @param string $format                     The format of the message
     * @param string $dateFormat                 The format of the timestamp: one supported by DateTime::format
     * @param bool   $allowInlineLineBreaks      Whether to allow inline line breaks in log entries
     * @param bool   $ignoreEmptyContextAndExtra
     */
    public function __construct($format = null, $dateFormat = null, $allowInlineLineBreaks = false, $ignoreEmptyContextAndExtra = false, $includeStacktraces = false)
    {
        $this->format = $format ?: static::SIMPLE_FORMAT;
        $this->allowInlineLineBreaks = $allowInlineLineBreaks;
        $this->ignoreEmptyContextAndExtra = $ignoreEmptyContextAndExtra;
        $this->includeStacktraces($includeStacktraces);
        parent::__construct($dateFormat);
    }

    /**
     * Indent stack traces to separate them a bit from the main log record messages
     *
     * @param  string $indent The string used to indent, for example "    "
     * @return $this
     */
    public function indentStacktraces($indent)
    {
        $this->indentStacktraces = $indent;

        return $this;
    }
    
    /**
     * Setting a base path will hide the base path from exception and stack trace file names to shorten them
     * @return $this
     */
    public function setBasePath($path = '')
    {
        if ($path !== '') {
            $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }

        $this->basePath = $path;

        return $this;
    }

    /**
     * @return $this
     */
    public function includeStacktraces($include = true, $parser = null)
    {
        $this->includeStacktraces = $include;
        if ($this->includeStacktraces) {
            $this->allowInlineLineBreaks = true;
            $this->stacktracesParser = $parser;
        }

        return $this;
    }

    public function allowInlineLineBreaks($allow = true)
    {
        $this->allowInlineLineBreaks = $allow;
    }

    public function ignoreEmptyContextAndExtra($ignore = true)
    {
        $this->ignoreEmptyContextAndExtra = $ignore;
    }

    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {
        $vars = parent::format($record);

        $output = $this->format;

        foreach ($vars['extra'] as $var => $val) {
            if (false !== strpos($output, '%extra.'.$var.'%')) {
                $output = str_replace('%extra.'.$var.'%', $this->stringify($val), $output);
                unset($vars['extra'][$var]);
            }
        }


        foreach ($vars['context'] as $var => $val) {
            if (false !== strpos($output, '%context.'.$var.'%')) {
                $output = str_replace('%context.'.$var.'%', $this->stringify($val), $output);
                unset($vars['context'][$var]);
            }
        }

        if ($this->ignoreEmptyContextAndExtra) {
            if (empty($vars['context'])) {
                unset($vars['context']);
                $output = str_replace('%context%', '', $output);
            }

            if (empty($vars['extra'])) {
                unset($vars['extra']);
                $output = str_replace('%extra%', '', $output);
            }
        }

        foreach ($vars as $var => $val) {
            if (false !== strpos($output, '%'.$var.'%')) {
                $output = str_replace('%'.$var.'%', $this->stringify($val), $output);
            }
        }

        // remove leftover %extra.xxx% and %context.xxx% if any
        if (false !== strpos($output, '%')) {
            $output = preg_replace('/%(?:extra|context)\..+?%/', '', $output);
        }

        return $output;
    }

    public function formatBatch(array $records)
    {
        $message = '';
        foreach ($records as $record) {
            $message .= $this->format($record);
        }

        return $message;
    }

    public function stringify($value)
    {
        return $this->replaceNewlines($this->convertToString($value));
    }

    protected function normalizeException($e, $depth = 0)
    {
        $str = $this->formatException($e);

        $previous = $e->getPrevious();
        while ($previous instanceof \Throwable) {
            $depth++;
            if ($depth > $this->maxNormalizeDepth) {
                $str .= "\n[previous exception] Over " . $this->maxNormalizeDepth . ' levels deep, aborting normalization';
                break;
            }
            $str .= "\n[previous exception] " . $this->formatException($previous);
            $previous = $previous->getPrevious();
        }

        return $str;
    }

    private function formatException($e)
    {
        $str = '[object] (' . Utils::getClass($e) . '(code: ' . $e->getCode();
        if ($e instanceof \SoapFault) {
            if (isset($e->faultcode)) {
                $str .= ' faultcode: ' . $e->faultcode;
            }

            if (isset($e->faultactor)) {
                $str .= ' faultactor: ' . $e->faultactor;
            }

            if (isset($e->detail)) {
                if (\is_string($e->detail)) {
                    $str .= ' detail: ' . $e->detail;
                } elseif (\is_object($e->detail) || \is_array($e->detail)) {
                    $str .= ' detail: ' . $this->toJson($e->detail, true);
                }
            }
        }

        $file = $e->getFile();
        if ($this->basePath !== '') {
            $file = preg_replace('{^'.preg_quote($this->basePath).'}', '', $file);
        }

        $str .= '): ' . $e->getMessage() . ' at ' . str_replace(DIRECTORY_SEPARATOR, '/', $file) . ':' . $e->getLine() . ')';

        if ($this->includeStacktraces) {
            $str .= $this->stacktracesParser($e);
        }

        return $str;
    }
    
    private function stacktracesParser($e)
    {
        $trace = $e->getTraceAsString();

        if ($this->basePath !== '') {
            $trace = preg_replace('{^(#\d+ )' . preg_quote($this->basePath) . '}m', '$1', $trace) ?: $trace;
        }

        if ($this->stacktracesParser !== null) {
            $trace = $this->stacktracesParserCustom($trace);
        }

        if ($this->indentStacktraces !== '') {
            $trace = str_replace("\n", "\n{$this->indentStacktraces}", $trace);
        }

        return !$trace ? '' : "\n{$this->indentStacktraces}[stacktrace]\n{$this->indentStacktraces}" . str_replace(DIRECTORY_SEPARATOR, '/', $trace) . "\n";
    }

    private function stacktracesParserCustom($trace)
    {
        return implode("\n", array_filter(array_map($this->stacktracesParser, explode("\n", $trace)), function ($line) { return is_string($line) && trim($line) !== ''; }));
    }

    protected function convertToString($data)
    {
        if (null === $data || is_bool($data)) {
            return var_export($data, true);
        }

        if (is_scalar($data)) {
            return (string) $data;
        }

        if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
            return $this->toJson($data, true);
        }

        return str_replace('\\/', '/', $this->toJson($data, true));
    }

    protected function replaceNewlines($str)
    {
        if ($this->allowInlineLineBreaks) {
            if (0 === strpos($str, '{')) {
                return str_replace(array('\r', '\n'), array("\r", "\n"), $str);
            }

            return $str;
        }

        return str_replace(array("\r\n", "\r", "\n"), ' ', $str);
    }
}
