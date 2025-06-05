<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function hprl_generate_excel( array $rows ) {
    if ( ! class_exists( 'ZipArchive' ) ) {
        return false;
    }
    $temp = tempnam( sys_get_temp_dir(), 'hprl' );
    $zip = new ZipArchive();
    if ( $zip->open( $temp, ZipArchive::CREATE ) !== true ) {
        return false;
    }
    $zip->addFromString('[Content_Types].xml',
        '<?xml version="1.0" encoding="UTF-8"?>\n<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">\n' .
        '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>\n' .
        '<Default Extension="xml" ContentType="application/xml"/>\n' .
        '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>\n' .
        '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>\n' .
        '</Types>' );
    $zip->addFromString('_rels/.rels',
        '<?xml version="1.0" encoding="UTF-8"?>\n<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">\n' .
        '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>\n' .
        '</Relationships>' );
    $zip->addFromString('xl/_rels/workbook.xml.rels',
        '<?xml version="1.0" encoding="UTF-8"?>\n<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">\n' .
        '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>\n' .
        '</Relationships>' );
    $zip->addFromString('xl/workbook.xml',
        '<?xml version="1.0" encoding="UTF-8"?>\n<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">\n' .
        '<sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets></workbook>' );

    $sheet = '<?xml version="1.0" encoding="UTF-8"?>\n<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
    foreach ( $rows as $r_idx => $row ) {
        $sheet .= '<row r="' . ( $r_idx + 1 ) . '">';
        foreach ( array_values( $row ) as $c_idx => $val ) {
            $col = chr(65 + $c_idx) . ( $r_idx + 1 );
            $val = htmlspecialchars( (string) $val, ENT_QUOTES | ENT_XML1 );
            $sheet .= '<c r="' . $col . '" t="inlineStr"><is><t>' . $val . '</t></is></c>';
        }
        $sheet .= '</row>';
    }
    $sheet .= '</sheetData></worksheet>';

    $zip->addFromString('xl/worksheets/sheet1.xml', $sheet );
    $zip->close();
    $data = file_get_contents( $temp );
    unlink( $temp );
    return $data;
}

function hprl_download_excel( array $rows, $filename = 'results.xlsx' ) {
    $xlsx = hprl_generate_excel( $rows );
    if ( $xlsx === false ) {
        return false;
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($xlsx));
    echo $xlsx;
    exit;
}
