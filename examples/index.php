<?php

require __DIR__.'/../vendor/autoload.php';

use thiagoalessio\TesseractOCR\TesseractOCR;

$imagesDir = __DIR__ . '/images/';
$images = glob($imagesDir."*.png");

$cacheDir = __DIR__ . '/cache/';

$parser = new \ReceiptParser\Parser(new \ReceiptParser\Parsers\TankReceiptParser());
$result = new \Illuminate\Support\Collection();

$ocr = new TesseractOCR();
$ocr->executable('/opt/homebrew/opt/tesseract/bin/tesseract');
$ocr->lang('nld', 'us');

foreach($images as $image) {
    $fileInfo = new SplFileInfo($image);
    $cachePath = $cacheDir.$fileInfo->getBasename('.'.$fileInfo->getExtension()).'.json';
    if(file_exists($cachePath)) {
        $data = json_decode(file_get_contents($cachePath), true);

        if ($item = $parser->fromArray($data)) {
            $item->source = $cachePath;
            $result->push($item);
        }
    } else if($item = $parser->fromImage($image, $ocr)) {
        $item->source = $image;
        $result->push($item);
    }

}

$data = $result->toArray();
$class = new ReflectionClass($data[1]);
$properties = $class->getProperties(ReflectionProperty::IS_PUBLIC);
$heading = [];
foreach ($properties as $property) {
    $heading[] = $property->getName();
}
$out = 'test.csv';
$file = fopen($out, 'w');
fputcsv($file, $heading);
$out = fopen('php://output', 'w');
foreach($data as $line)
{
    $line = $line->toArray();
    $line['date'] = $line['date']->format('Y-m-d');
    fputcsv($file, $line);
}
fclose($file);
dd($result->toArray());