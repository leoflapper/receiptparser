<?php


namespace ReceiptParser\DTO;


use Spatie\DataTransferObject\DataTransferObject;

final class TankReceiptDto extends DataTransferObject
{
    public ?string $tankstation;
    public ?\DateTime $date;
    public ?float $liters;
    public ?string $pricePerLiter;
    public ?string $subtotal;
    public ?string $tax;
    public ?string $total;
    public ?string $source;
}