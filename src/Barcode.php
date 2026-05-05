<?php

namespace Cards;

use Picqer\Barcode\BarcodeGeneratorSVG;
use Picqer\Barcode\Exceptions\BarcodeException;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRMarkupSVG;

class Barcode
{
    public static function render(string $number, string $type): string
    {
        if ($type === 'qr') {
            return self::renderQR($number);
        }
        return self::renderBarcode($number, $type);
    }

    private static function renderQR(string $data): string
    {
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => false,
            'addQuietzone' => true,
            'quietzoneSize' => 2,
            'scale' => 10,
        ]);

        return (new QRCode($options))->render($data);
    }

    private static function renderBarcode(string $number, string $type): string
    {
        $generator = new BarcodeGeneratorSVG();

        $typeMap = [
            'ean_13' => $generator::TYPE_EAN_13,
            'ean_8' => $generator::TYPE_EAN_8,
            'code_128' => $generator::TYPE_CODE_128,
            'code_39' => $generator::TYPE_CODE_39,
            'itf' => $generator::TYPE_INTERLEAVED_2_5,
            'codabar' => $generator::TYPE_CODABAR,
        ];

        $barcodeType = $typeMap[$type] ?? $generator::TYPE_CODE_128;

        try {
            return $generator->getBarcode($number, $barcodeType, 2, 100);
        } catch (BarcodeException $e) {
            // Fallback to CODE128
            try {
                return $generator->getBarcode($number, $generator::TYPE_CODE_128, 2, 100);
            } catch (BarcodeException $e2) {
                return '<p>Unable to generate barcode</p>';
            }
        }
    }
}
