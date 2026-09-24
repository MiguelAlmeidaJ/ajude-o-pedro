<?php
declare(strict_types=1);

function pix_field(string $id, string $value): string
{
    return $id . str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT) . $value;
}

function pix_ascii(string $value, int $max): string
{
    $value = strtoupper(trim($value));
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    $value = preg_replace('/[^A-Z0-9 .\-]/', '', $value) ?: '';
    return substr($value, 0, $max);
}

function pix_crc16(string $payload): string
{
    $crc = 0xFFFF;
    $poly = 0x1021;

    for ($i = 0, $len = strlen($payload); $i < $len; $i++) {
        $crc ^= (ord($payload[$i]) << 8);
        for ($bit = 0; $bit < 8; $bit++) {
            $crc = ($crc & 0x8000) ? (($crc << 1) ^ $poly) : ($crc << 1);
            $crc &= 0xFFFF;
        }
    }

    return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}

function pix_payload(string $key, string $receiverName, string $receiverCity, float $amount, string $txid): string
{
    $merchant = pix_field('00', 'br.gov.bcb.pix') . pix_field('01', trim($key));
    $payload =
        pix_field('00', '01') .
        pix_field('26', $merchant) .
        pix_field('52', '0000') .
        pix_field('53', '986') .
        pix_field('54', number_format($amount, 2, '.', '')) .
        pix_field('58', 'BR') .
        pix_field('59', pix_ascii($receiverName, 25)) .
        pix_field('60', pix_ascii($receiverCity, 15)) .
        pix_field('62', pix_field('05', preg_replace('/[^A-Za-z0-9]/', '', $txid) ?: '***'));

    $payload .= '6304';
    return $payload . pix_crc16($payload);
}