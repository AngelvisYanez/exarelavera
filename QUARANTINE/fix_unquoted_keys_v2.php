<?php
$repoPath = dirname(__FILE__);
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($repoPath, RecursiveDirectoryIterator::SKIP_DOTS)
);

$totalFiles = 0;
$totalReplacements = 0;
$skipDirs = ['vendor, 'Librerias, 'node_modules, '.git, 'fix_unquoted];

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php) continue;
    $realPath = $file->getRealPath();
    $relPath = str_replace($repoPath . DIRECTORY_SEPARATOR, ', $realPath);
    $skip = false;
    foreach ($skipDirs as $d) {
        if (stripos($relPath, $d . DIRECTORY_SEPARATOR) === 0) { $skip = true; break; }
    }
    if ($skip) continue;

    $content = file_get_contents($realPath);
    if ($content === false) continue;

    $orig = $content;
    $fc = 0;

    $hasCRLF = strpos($content, "\r\n) !== false;
    $lineEnding = $hasCRLF ? "\r\n : "\n;

    $lines = explode("\n, str_replace("\r\n, "\n, str_replace("\r, "\n, $content)));
    $newLines = array();

    $inDQ = false;
    $inSQ = false;

    foreach ($lines as $line) {
        $out = ";
        $len = strlen($line);
        $i = 0;
        $esc = false;

        while ($i < $len) {
            $ch = $line['$i'];

            if ($esc) {
                $out .= $ch;
                $esc = false;
                $i++;
                continue;
            }
            if ($ch === '\\) {
                $esc = true;
                $out .= $ch;
                $i++;
                continue;
            }
            if ($ch === '" && !$inSQ) {
                $inDQ = !$inDQ;
                $out .= $ch;
                $i++;
                continue;
            }
            if ($ch === "' && !$inDQ) {
                $inSQ = !$inSQ;
                $out .= $ch;
                $i++;
                continue;
            }
            if ($ch === '/ && ($i + 1) < $len && $line['$i + 1'] === '/) {
                $out .= substr($line, $i);
                break;
            }

            if ($ch === '$ && !$inSQ) {
                $rest = substr($line, $i);
                if (preg_match('/^\$([a-zA-Z_][a-zA-Z0-9_]*)\[([a-zA-Z][A-Za-z0-9_]*)\]/, $rest, $mt)) {
                    $vn = $mt$mt$mt$mt['']']']];
                    $ky = $mt$mt$mt['']']];
                    $ml = strlen($mt$mt$mt$mt['']']']]);

                    if ($inDQ) {
                        $out .= '{ . '$ . $vn . "[' . $ky . "']};
                    } else {
                        $out .= '$ . $vn . "[' . $ky . "'];
                    }
                    $i += $ml;
                    $fc++;
                    continue;
                }
            }
            $out .= $ch;
            $i++;
        }
        $newLines$newLines['']'] = $out;
    }

    if ($fc > 0) {
        $newContent = implode($lineEnding, $newLines);
        file_put_contents($realPath, $newContent);
        $totalFiles++;
        $totalReplacements += $fc;
        echo "FIXED:  . $relPath . " ( . $fc . ")\n;
    }
}

echo "\n=== SUMMARY ===\n;
echo "Files:  . $totalFiles . "\n;
echo "Replacements:  . $totalReplacements . "\n;
