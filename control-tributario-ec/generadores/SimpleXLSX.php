<?php
/**
 * Generador XLSX mínimo (OOXML) sin Composer
 */
class SimpleXLSX
{
    private $sheets = array();
    private $sheetIndex = 0;

    public function addSheet($name) {
        $this->sheets[] = array('name' => self::sanitizeName($name), 'rows' => array());
        return $this->sheetIndex = count($this->sheets) - 1;
    }

    public function writeRow($sheet, array $cells, $styles = null) {
        $this->sheets[$sheet]['rows'][] = array('cells' => $cells, 'styles' => $styles);
    }

    public function output($filename) {
        $tmp = sys_get_temp_dir() . '/cte_' . uniqid() . '.xlsx';
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear XLSX');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rels());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());

        foreach ($this->sheets as $i => $sheet) {
            $n = $i + 1;
            $zip->addFromString("xl/worksheets/sheet{$n}.xml", $this->sheetXml($sheet));
            if ($i === 0) {
                $zip->addFromString('xl/styles.xml', $this->stylesXml());
                $zip->addFromString('xl/_rels/styles.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>');
            }
        }

        $zip->close();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        readfile($tmp);
        @unlink($tmp);
    }

    private static function sanitizeName($n) {
        return mb_substr(preg_replace('/array(\\\\\\/?*\\[\\]:)/', '', $n), 0, 31);
    }

    private function contentTypes() {
        $sheets = '';
        for ($i = 1; $i <= count($this->sheets); $i++) {
            $sheets .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
' . $sheets . '
</Types>';
    }

    private function rels() {
        return '<?xml version="1.0"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';
    }

    private function workbook() {
        $sheets = '';
        foreach ($this->sheets as $i => $s) {
            $n = $i + 1;
            $sheets .= '<sheet name="' . htmlspecialchars($s['name']) . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
        }
        return '<?xml version="1.0"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
<sheets>' . $sheets . '</sheets>
</workbook>';
    }

    private function workbookRels() {
        $rels = '';
        for ($i = 1; $i <= count($this->sheets); $i++) {
            $rels .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }
        $n = count($this->sheets) + 1;
        $rels .= '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        return '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels . '</Relationships>';
    }

    private function stylesXml() {
        return '<?xml version="1.0"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<fonts count="2"><font><sz val="11"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/></font></fonts>
<fills count="4">
<fill><patternFill patternType="none"/></fill>
<fill><patternFill patternType="gray125"/></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FF1F4E79"/></patternFill></fill>
<fill><patternFill patternType="solid"><fgColor rgb="FFD6E4F0"/></patternFill></fill>
</fills>
<cellXfs count="3">
<xf numFmtId="0" fontId="0" fillId="0"/><xf numFmtId="0" fontId="1" fillId="2" applyFont="1" applyFill="1"/>
<xf numFmtId="0" fontId="0" fillId="3" applyFill="1"/>
</cellXfs>
</styleSheet>';
    }

    private function sheetXml(array $sheet) {
        $rows = '';
        $r = 1;
        foreach ($sheet['rows'] as $row) {
            $cells = '';
            $c = 0;
            foreach ($row['cells'] as $cell) {
                $col = self::colLetter($c++);
                $style = '';
                if (!empty($row['styles'][$c - 1])) {
                    $style = ' s="' . (int) $row['styles'][$c - 1] . '"';
                }
                if (is_numeric($cell)) {
                    $cells .= '<c r="' . $col . $r . '"' . $style . '><v>' . $cell . '</v></c>';
                } else {
                    $cells .= '<c r="' . $col . $r . '" t="inlineStr"' . $style . '><is><t>' . htmlspecialchars((string) $cell) . '</t></is></c>';
                }
            }
            $rows .= '<row r="' . $r . '">' . $cells . '</row>';
            $r++;
        }
        return '<?xml version="1.0"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
<sheetData>' . $rows . '</sheetData>
</worksheet>';
    }

    private static function colLetter($c) {
        $l = '';
        while ($c >= 0) {
            $l = chr(65 + ($c % 26)) . $l;
            $c = intdiv($c, 26) - 1;
        }
        return $l;
    }
}
