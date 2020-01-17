<?php
function loadCsv($csvFilePath){
    $csv = new SplFileObject($csvFilePath);
    $csv->setFlags(
        SplFileObject::READ_CSV |
        SplFileObject::SKIP_EMPTY |
        SplFileObject::READ_AHEAD
    );
    $records = array();
    foreach ($csv as $i => $row) {
        if ($i === 0) {
            foreach ($row as $j => $col) {
                if ($j == 0) {
                    $colbook[$j] = "ID";
                } else {
                    $colbook[$j] = $col;
                }
            }
            continue;
        }
        // 2行目以降はデータ行として取り込み
        $line = array();
        foreach ($colbook as $j => $col) {
            $line[$colbook[$j]] = @$row[$j];
        }
        $records[] = $line;
    }
    return ['colbook'=>$colbook, 'records'=>$records];
}