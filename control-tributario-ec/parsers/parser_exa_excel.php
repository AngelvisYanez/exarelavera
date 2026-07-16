<?php
function parse_exa_html_excel($filePath) {
    $content = file_get_contents($filePath);
    $dom = new DOMDocument();
    // Suprimir warnings por HTML malformado
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $content);
    
    // Buscar la tabla que contenga "FORMULARIO 104"
    $tables = $dom->getElementsByTagName('table');
    $targetTable = null;
    foreach ($tables as $table) {
        if (strpos(strtoupper($table->textContent), 'FORMULARIO 104') !== false) {
            $targetTable = $table;
            break;
        }
    }
    
    if (!$targetTable) {
        return array('error' => 'No se encontró la tabla de EXA en el archivo Excel.');
    }
    
    $grid = array();
    $trs = $targetTable->getElementsByTagName('tr');
    $rowIndex = 0;
    foreach ($trs as $tr) {
        if (!isset($grid[$rowIndex])) $grid[$rowIndex] = array();
        
        $colIndex = 0;
        $cells = array();
        foreach ($tr->childNodes as $child) {
            if ($child->nodeName === 'td' || $child->nodeName === 'th') {
                $cells[] = $child;
            }
        }
        
        foreach ($cells as $cell) {
            while (isset($grid[$rowIndex][$colIndex])) {
                $colIndex++;
            }
            
            $rowspan = $cell->getAttribute('rowspan') ? (int)$cell->getAttribute('rowspan') : 1;
            $colspan = $cell->getAttribute('colspan') ? (int)$cell->getAttribute('colspan') : 1;
            $text = trim($cell->textContent);
            
            for ($r = 0; $r < $rowspan; $r++) {
                for ($c = 0; $c < $colspan; $c++) {
                    if (!isset($grid[$rowIndex + $r])) $grid[$rowIndex + $r] = array();
                    $grid[$rowIndex + $r][$colIndex + $c] = $text;
                }
            }
        }
        $rowIndex++;
    }
    
    $maxCols = 0;
    foreach ($grid as $r => $cols) {
        if (empty($cols)) continue;
        $m = max(array_keys($cols));
        if ($m > $maxCols) $maxCols = $m;
    }
    
    $finalGrid = array();
    foreach ($grid as $r => $cols) {
        $rowArray = array();
        for ($c = 0; $c <= $maxCols; $c++) {
            $rowArray[] = isset($cols[$c]) ? $cols[$c] : '';
        }
        $finalGrid[] = $rowArray;
    }
    
    return array('exa_data' => $finalGrid);
}
