<?php

namespace ReceiptParser;


use ReceiptParser\Parsers\ParserInterface;
use ReceiptParser\Parsers\Tango;
use Spatie\DataTransferObject\DataTransferObject;
use thiagoalessio\TesseractOCR\TesseractOCR;

class Parser
{
    public function __construct(ParserInterface $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @param string $imagePath
     * @param TesseractOCR|null $ocr
     * @return DataTransferObject
     * @throws \thiagoalessio\TesseractOCR\TesseractOcrException
     */
    public function fromImage(string $imagePath, TesseractOCR $ocr = null)
    {
        if(!$ocr) {
            $ocr = new TesseractOCR();
        }
        $ocr = $ocr->image($imagePath);
        $data = explode(PHP_EOL, $ocr->run());
        $data['source'] = $imagePath;

        return $this->fromArray($data);
    }

    /**
     * @param array $data
     * @return DataTransferObject
     */
    public function fromArray(array $data): DataTransferObject
    {
        return $this->parser->parse($data);
    }

}