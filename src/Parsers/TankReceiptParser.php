<?php


namespace ReceiptParser\Parsers;


use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Support\Collection;
use ReceiptParser\DTO\TankReceiptDto;
use Spatie\DataTransferObject\DataTransferObject;

class TankReceiptParser implements ParserInterface
{

    public string $id = 'tank_receipt';

    protected $castErrors = [];

    protected $matchDatas = [
        'Date' => [
            'id' => 'date',
            'strpos' => 'Datum',
            'regex' => '/Datum (.*)/',
            'class' => \DateTime::class
        ],
        'Liters' => [
            'id' => 'liters',
            'strpos' => 'Volume',
            'regex' => '/Volume (.*) ?/',
            'cast' => 'float'
        ],
        'PricePerLiter' => [
            'id' => 'pricePerLiter',
            'strpos' => 'Prijs',
            'regex' => '/Prijs € (\d*\,\.\d*)/',
            'cast' => 'Money',
            'cast_options' => [
                'currency' => 'EUR'
            ]
        ],
        'Subtotal' => [
            'id' => 'subtotal',
            'strpos' => 'Netto',
            'regex' => ['/Netto€(\d*[\,\.]\d*)/', '/Netto €(\d*[\,\.]\d*)/', '/Netto € (\d*[\,\.]\d*)/'],
            'cast' => 'Money',
            'cast_options' => [
                'currency' => 'EUR'
            ]
        ],
        'Tax' => [
            'id' => 'tax',
            'strpos' => 'BTW ',
            'regex' => ['/BTW 21[\,\.]00 € (\d*[\,\.]\d*)/', '/BTW 21[\,\.]00 €(\d*[\,\.]\d*)/', '/BTW 21[\,\.]00€ (\d*[\,\.]\d*)/', '/BTW21[\,\.]00€(\d*[\,\.]\d*)/'],
            'cast' => 'Money',
        ],
        'Total' => [
            'id' => 'total',
            'strpos' => 'TOTAAL',
            'regex' => ['/TOTAAL € (\d*[\,\.]\d*)/', '/TOTAAL € (\s\d*[\,\.]\d*)/', '/TOTAAL €(\s\d*[\,\.]\d*)/', '/TOTAAL € (\s\d*[\,\.]\d*)/'],
            'cast' => 'Money',
        ],
    ];

    public function parse(array $data): DataTransferObject
    {
        return $this->format(collect($data));
    }

    private function format(Collection $data)
    {
        $result = new Collection();
        $result->put('tankstation', $data->first());
        foreach ($this->matchDatas as $matchData) {
            $result[$matchData['id']] = $this->matchItem($matchData, $data);
        }

        if(!$result->get('date') instanceof \DateTime) {
            $result->put('date', new \DateTime());
        }

        if(!is_float($result->get('liters'))) {
            $result->put('liters', (float)$result->get('liters'));
        }

        return new TankReceiptDto($result->toArray());
    }

    /**
     * @param array $matchData
     * @param Collection $data
     * @return float|mixed
     */
    public function matchItem(array $matchData, Collection $data)
    {

        foreach ($data as $key => $value) {

            $value = str_replace(['=', '?', '%', '  '], '', $value);

            if(stripos(strtolower($value), $matchData['strpos']) !== false) {
                $originalValue = $value;

                if(isset($matchData['regex'])) {

                    if(!is_array($matchData['regex'])) {
                        $matchData['regex'] = [$matchData['regex']];
                    }

                    foreach ($matchData['regex'] as $regex) {

                        if(preg_match($regex, $value, $matches)) {
                            if(isset($matches[1])) {
                                $value = $matches[1];
                                break;
                            }

                        }
                    }


                }

                $value = str_replace($matchData['strpos'], '', $value);

                if(isset($matchData['class'])) {
                    try {
                        $value = new $matchData['class']($value);
                    } catch (\Exception $e) {
                        $this->castErrors[] = [
                            'key' => $key,
                            'originalValue' => $originalValue,
                            'value' => $value,
                            'exceptionMessage' => $e->getMessage(),
                            'keyRegex' => $matchData
                        ];
                    }
                }


                if(isset($matchData['cast'])) {
                    switch ($matchData['cast']) {
                        case 'float':
                            $value = (float)$value;
                            break;
                        case 'Money':
                            $value = str_replace([',', ',,', ' '], '.', $value);
                            $value = str_replace('..', '.', $value);

                            try {
                                $money = Money::of($value, ''   , null, RoundingMode::UP);
                                $value = (string)$money->getAmount();
                                $value = str_replace('.', ',', $value);
                            } catch (\Exception $e) {
                                $this->castErrors[] = [
                                    'key' => $key,
                                    'originalValue' => $originalValue,
                                    'value' => $value,
                                    'exceptionMessage' => $e->getMessage(),
                                    'keyRegex' => $matchData
                                ];
                            }

                            break;
                    }

                }
                return $value;
            }
        }
        return '';
    }

}