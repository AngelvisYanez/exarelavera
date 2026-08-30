<?php
$excludeDirs = array('vendor', 'Librerias', 'frontend-next', 'node_modules');

function getFiles($dir, $excludeDirs) {
    $results = array();
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            if (in_array($item, $excludeDirs)) continue;
            $results = array_merge($results, getFiles($path, $excludeDirs));
        } elseif (substr($item, -4) === '.php') {
            $results[] = $path;
        }
    }
    return $results;
}

function processFile($filePath) {
    $content = file_get_contents($filePath);
    if ($content === false) return array(false, 0);

    $tokens = token_get_all($content);
    $count = count($tokens);
    $fixes = array();

    $inDblString = false;
    $inSingleString = false;
    $inHeredoc = false;

    for ($i = 0; $i < $count; $i++) {
        $tok = $tokens[$i];

        if (is_array($tok)) {
            $tokenId = $tok[0];

            if ($tokenId === T_START_HEREDOC || $tokenId === T_START_NOWDOC) {
                $inHeredoc = true;
                continue;
            }
            if ($tokenId === T_END_HEREDOC) {
                $inHeredoc = false;
                continue;
            }

            if ($tokenId === T_VARIABLE && !$inSingleString && !$inHeredoc) {
                $next = ($i + 1 < $count) ? $tokens[$i + 1] : null;
                if ($next === null) continue;
                $nextChar = is_array($next) ? $next[1] : $next;
                $varName = $tok[1];

                if ($nextChar === '[') {
                    $line = $tok[2];
                    $depth = 1;
                    $j = $i + 2;
                    while ($j < $count && $depth > 0) {
                        $t = $tokens[$j];
                        if (!is_array($t)) {
                            if ($t === '[') $depth++;
                            elseif ($t === ']') $depth--;
                        }
                        if ($depth > 0) $j++;
                    }
                    if ($depth !== 0) continue;
                    $closeBracket = $tokens[$j];
                    $closeLine = is_array($closeBracket) ? $closeBracket[2] : $line;
                    if ($line !== $closeLine) continue;

                    $keyTokens = array();
                    for ($k = $i + 2; $k < $j; $k++) {
                        $keyTokens[] = $tokens[$k];
                    }

                    $isSimpleStringKey = false;
                    $keyRaw = '';
                    if (count($keyTokens) === 1 && is_array($keyTokens[0])) {
                        $kt = $keyTokens[0];
                        if ($kt[0] === T_STRING) {
                            $isSimpleStringKey = true;
                            $keyRaw = $kt[1];
                        }
                    }
                    if (!$isSimpleStringKey) continue;

                    $safeKey = str_replace("'", "\\'", $keyRaw);
                    $quoted = "'" . $safeKey . "'";
                    $original = $varName . '[' . $keyRaw . ']';

                    if ($inDblString) {
                        $replacement = '{' . $varName . '[' . $quoted . ']}';
                    } else {
                        $replacement = $varName . '[' . $quoted . ']';
                    }

                    $fixes[] = array(
                        'line' => $line,
                        'original' => $original,
                        'replacement' => $replacement,
                    );
                }

                if ($nextChar === '{') {
                    $line = $tok[2];
                    $depth = 1;
                    $j = $i + 2;
                    while ($j < $count && $depth > 0) {
                        $t = $tokens[$j];
                        if (!is_array($t)) {
                            if ($t === '{') $depth++;
                            elseif ($t === '}') $depth--;
                        }
                        if ($depth > 0) $j++;
                    }
                    if ($depth !== 0) continue;
                    $closeBrace = $tokens[$j];
                    $closeLine = is_array($closeBrace) ? $closeBrace[2] : $line;
                    if ($line !== $closeLine) continue;

                    $keyParts = array();
                    for ($k = $i + 2; $k < $j; $k++) {
                        $tk = $tokens[$k];
                        $keyParts[] = is_array($tk) ? $tk[1] : $tk;
                    }
                    $keyRaw = trim(implode('', $keyParts));

                    if (preg_match('/^\'([^\']*)\'$/', $keyRaw, $km)) {
                        $quoted = "'" . $km[1] . "'";
                    } elseif (preg_match('/^"([^"]*)"$/', $keyRaw, $km)) {
                        $quoted = "'" . $km[1] . "'";
                    } else {
                        $quoted = "'" . str_replace("'", "\\'", $keyRaw) . "'";
                    }

                    $original = $varName . '{' . $keyRaw . '}';
                    $replacement = $varName . '[' . $quoted . ']';

                    $fixes[] = array(
                        'line' => $line,
                        'original' => $original,
                        'replacement' => $replacement,
                    );
                }
            }
        } else {
            if (!$inHeredoc) {
                if ($tok === '"' && !$inSingleString) {
                    $inDblString = !$inDblString;
                } elseif ($tok === "'" && !$inDblString) {
                    $inSingleString = !$inSingleString;
                }
            }
        }
    }

    if (empty($fixes)) return array(false, 0);

    $lines = explode("\n", $content);
    $appliedCount = 0;

    foreach ($fixes as $fix) {
        $lineIdx = $fix['line'] - 1;
        if ($lineIdx < 0 || $lineIdx >= count($lines)) continue;
        $lineText = $lines[$lineIdx];

        $pos = strpos($lineText, $fix['original']);
        if ($pos === false) continue;

        $lines[$lineIdx] = substr_replace($lineText, $fix['replacement'], $pos, strlen($fix['original']));
        $appliedCount++;
    }

    if ($appliedCount === 0) return array(false, 0);

    $newContent = implode("\n", $lines);
    if ($newContent !== $content) {
        file_put_contents($filePath, $newContent);
        return array(true, $appliedCount);
    }
    return array(false, 0);
}

if (basename($argv[0] ?? '') === basename(__FILE__)) {
    $files = getFiles(__DIR__, $excludeDirs);
    $totalFiles = 0;
    $totalReplacements = 0;
    $changedFiles = array();

    foreach ($files as $file) {
        list($fileChanged, $fileReplacements) = processFile($file);
        if ($fileChanged) {
            $changedFiles[] = $file;
            $totalReplacements += $fileReplacements;
            $relPath = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $file);
            echo "$relPath ($fileReplacements fixes)\n";
        }
        $totalFiles++;
    }

    echo "\nProcessed $totalFiles files\n";
    echo "Changed " . count($changedFiles) . " files with $totalReplacements replacements\n";
}
