<?php


namespace ReceiptParser\Parsers;


use Spatie\DataTransferObject\DataTransferObject;

interface ParserInterface
{
    public function parse(array $data): DataTransferObject;
}