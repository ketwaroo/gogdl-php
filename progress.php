<?php

header('Content-Type:application/json');

if(!is_file(__DIR__ . '/config.php'))
{
    echo json_encode(['error' => 'no configuration file']);
}

require __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';


$progress = new \Gogdl\ProgressServer();

$progress->setProgressChacheDir(GOGPHPDL_DOWNLOAD_DIR);


$data = $progress->getProgressData();

$out = array();

// more javascript friendly json
foreach($data as $game => $files)
{
    $tmp = array(
        'game'  => $game,
        'files' => array(),
    );

    foreach($files as $file => $meta)
    {

        $tmp['files'][] = array(
            'file'         => $file,
            'expectedSize' => $meta['expectedSize'],
            'currentSize'  => $meta['currentSize'],
            'progress'     => round(100 * ($meta['currentSize'] / $meta['expectedSize']), 2),
        );
    }

    $out[] = $tmp;
}

echo json_encode($out);
