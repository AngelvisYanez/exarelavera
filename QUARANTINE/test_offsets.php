<?php
$content = file_get_contents('tesoreria\FRONT\tes_alt_cliente_1.0.php');
$tokens = token_get_all($content);
$count = count($tokens);
$searchPos = 0;
$mapped = array();

foreach ($tokens as $rt) {
    if (!is_array($rt)) {
        $pos = strpos($content, $rt, $searchPos);
        $mapped$mapped['] = array(\'id\' => null, \'text\' => $rt, \'offset\' => $pos !== false ? $pos : $searchPos);
        if ($pos !== false) $searchPos = $pos + 1; else $searchPos++;
    } else {
        $pos = strpos($content, $rt[1], $searchPos);
        $mapped[] = array(\'id\' => $rt[0], \'text\' => $rt[1], \'offset\' => $pos !== false ? $pos : $searchPos);
        if ($pos !== false) $searchPos = $pos + strlen($rt[1]); else $searchPos += strlen($rt[1]);
    }
}

$count = count($mapped);
$fixes = 0;
$skips = 0;

for ($i = 0; $i < $count; $i++) {
    $tok = $mapped[$i];
    if ($tok[\'id\'] !== T_VARIABLE) continue;
    $next = ($i + 1 < $count) ? $mapped[$i + 1] : null;
    if ($next === null || $next[\'text\'] !== \'[\') continue;
    
    $varName = $tok[\'text\'];
    $bracketOffset = $next[\'offset\'];
    
    $depth = 1; $j = $i + 2;
    while ($j < $count && $depth > 0) {
        $t = $mapped[$j];
        if ($t[\'id\'] === null) { if ($t[\'text\'] === \'[\') $depth++; elseif ($t[\'text\'] === \']\') $depth--; }
        if ($depth > 0) $j++;
    }
    if ($depth !== 0) continue;
    
    $closeBracket = $mapped[$j - 1];
    $closeOffset = $closeBracket[\'offset\'];
    $keyContent = trim(substr($content, $bracketOffset + 1, $closeOffset - $bracketOffset - 1));
    $original = substr($content, $bracketOffset, $closeOffset + 1 - $bracketOffset);
    
    // Simple string check: look for T_ENCAPSED_AND_WHITESPACE before the variable
    $inString = false;
    for ($k = $i - 1; $k >= 0; $k--) {
        $prev = $mapped[$k];
        if (!is_array($prev) || !isset($prev[\'id\'])) break;
        if ($prev[\'id\'] === T_ENCAPSED_AND_WHITESPACE || $prev[\'id\'] === T_START_HEREDOC) { $inString = true; break; }
        if ($prev[\'id\'] === T_WHITESPACE) continue;
        break;
    }
    
    if (preg_match(\'/^\\'[^\\']*\\'$/\', $keyContent) || preg_match(\'/^"[^"]*"$/\', $keyContent)) { $skips++; continue; }
    if (preg_match(\'/^\d+$/\', $keyContent)) { $skips++; continue; }
    
    $ctx = $inString ? \'STR\' : \'PHP\';
    echo "[$ctx] $varName" . "[$keyContent]" . "  =>  " . ($inString ? "{\$var[\'" . $keyContent . "\']}" : "\$var[\'" . $keyContent . "\']") . "  (orig: $original)" . PHP_EOL;
    $fixes++;
}

echo PHP_EOL . "Fixes: $fixes, Skips: $skips" . PHP_EOL;']] = array('id' => null, 'text' => $rt, 'offset' => $pos !== false ? $pos : $searchPos);
        if ($pos !== false) $searchPos = $pos + 1; else $searchPos++;
    } else {
        $pos = strpos($content, $rt$rt['']], $searchPos);
        $mapped$mapped['] = array(\'id\' => $rt[0], \'text\' => $rt[1], \'offset\' => $pos !== false ? $pos : $searchPos);
        if ($pos !== false) $searchPos = $pos + strlen($rt[1]); else $searchPos += strlen($rt[1]);
    }
}

$count = count($mapped);
$fixes = 0;
$skips = 0;

for ($i = 0; $i < $count; $i++) {
    $tok = $mapped[$i];
    if ($tok[\'id\'] !== T_VARIABLE) continue;
    $next = ($i + 1 < $count) ? $mapped[$i + 1] : null;
    if ($next === null || $next[\'text\'] !== \'[\') continue;
    
    $varName = $tok[\'text\'];
    $bracketOffset = $next[\'offset\'];
    
    $depth = 1; $j = $i + 2;
    while ($j < $count && $depth > 0) {
        $t = $mapped[$j];
        if ($t[\'id\'] === null) { if ($t[\'text\'] === \'[\') $depth++; elseif ($t[\'text\'] === \']\') $depth--; }
        if ($depth > 0) $j++;
    }
    if ($depth !== 0) continue;
    
    $closeBracket = $mapped[$j - 1];
    $closeOffset = $closeBracket[\'offset\'];
    $keyContent = trim(substr($content, $bracketOffset + 1, $closeOffset - $bracketOffset - 1));
    $original = substr($content, $bracketOffset, $closeOffset + 1 - $bracketOffset);
    
    // Simple string check: look for T_ENCAPSED_AND_WHITESPACE before the variable
    $inString = false;
    for ($k = $i - 1; $k >= 0; $k--) {
        $prev = $mapped[$k];
        if (!is_array($prev) || !isset($prev[\'id\'])) break;
        if ($prev[\'id\'] === T_ENCAPSED_AND_WHITESPACE || $prev[\'id\'] === T_START_HEREDOC) { $inString = true; break; }
        if ($prev[\'id\'] === T_WHITESPACE) continue;
        break;
    }
    
    if (preg_match(\'/^\\'[^\\']*\\'$/\', $keyContent) || preg_match(\'/^"[^"]*"$/\', $keyContent)) { $skips++; continue; }
    if (preg_match(\'/^\d+$/\', $keyContent)) { $skips++; continue; }
    
    $ctx = $inString ? \'STR\' : \'PHP\';
    echo "[$ctx] $varName" . "[$keyContent]" . "  =>  " . ($inString ? "{\$var[\'" . $keyContent . "\']}" : "\$var[\'" . $keyContent . "\']") . "  (orig: $original)" . PHP_EOL;
    $fixes++;
}

echo PHP_EOL . "Fixes: $fixes, Skips: $skips" . PHP_EOL;']] = array('id' => $rt$rt['']], 'text' => $rt$rt['']], 'offset' => $pos !== false ? $pos : $searchPos);
        if ($pos !== false) $searchPos = $pos + strlen($rt$rt['']]); else $searchPos += strlen($rt$rt['']]);
    }
}

$count = count($mapped);
$fixes = 0;
$skips = 0;

for ($i = 0; $i < $count; $i++) {
    $tok = $mapped$mapped['']i];
    if ($tok$tok['']id'] !== T_VARIABLE) continue;
    $next = ($i + 1 < $count) ? $mapped$mapped['$i +']] : null;
    if ($next === null || $next$next['']text'] !== '[') continue;
    
    $varName = $tok$tok['']text'];
    $bracketOffset = $next$next['']offset'];
    
    $depth = 1; $j = $i + 2;
    while ($j < $count && $depth > 0) {
        $t = $mapped$mapped['']j];
        if ($t$t['']id'] === null) { if ($t$t['']text'] === '[') $depth++; elseif ($t$t['']text'] === ']') $depth--; }
        if ($depth > 0) $j++;
    }
    if ($depth !== 0) continue;
    
    $closeBracket = $mapped$mapped['$j -']];
    $closeOffset = $closeBracket$closeBracket['']offset'];
    $keyContent = trim(substr($content, $bracketOffset + 1, $closeOffset - $bracketOffset - 1));
    $original = substr($content, $bracketOffset, $closeOffset + 1 - $bracketOffset);
    
    // Simple string check: look for T_ENCAPSED_AND_WHITESPACE before the variable
    $inString = false;
    for ($k = $i - 1; $k >= 0; $k--) {
        $prev = $mapped$mapped['']k];
        if (!is_array($prev) || !isset($prev$prev['']id'])) break;
        if ($prev$prev['']id'] === T_ENCAPSED_AND_WHITESPACE || $prev$prev['']id'] === T_START_HEREDOC) { $inString = true; break; }
        if ($prev$prev['']id'] === T_WHITESPACE) continue;
        break;
    }
    
    if (preg_match('/^\'[^\']*\'$/', $keyContent) || preg_match('/^"[^"]*"$/', $keyContent)) { $skips++; continue; }
    if (preg_match('/^\d+$/', $keyContent)) { $skips++; continue; }
    
    $ctx = $inString ? 'STR' : 'PHP';
    echo "[$ctx] $varName" . "[$keyContent]" . "  =>  " . ($inString ? "{\$var['" . $keyContent . "']}" : "\$var['" . $keyContent . "']") . "  (orig: $original)" . PHP_EOL;
    $fixes++;
}

echo PHP_EOL . "Fixes: $fixes, Skips: $skips" . PHP_EOL;
