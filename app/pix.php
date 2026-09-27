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

function pix_is_valid_cpf(string $digits): bool
{
    if (!preg_match('/^\d{11}$/', $digits) || preg_match('/^(\d)\1{10}$/', $digits)) {
        return false;
    }

    for ($t = 9; $t < 11; $t++) {
        $sum = 0;
        for ($i = 0; $i < $t; $i++) {
            $sum += ((int) $digits[$i]) * (($t + 1) - $i);
        }

        $digit = ($sum * 10) % 11;
        if ($digit === 10) {
            $digit = 0;
        }

        if ($digit !== (int) $digits[$t]) {
            return false;
        }
    }

    return true;
}

function pix_is_valid_cnpj(string $digits): bool
{
    if (!preg_match('/^\d{14}$/', $digits) || preg_match('/^(\d)\1{13}$/', $digits)) {
        return false;
    }

    $weights1 = [5,4,3,2,9,8,7,6,5,4,3,2];
    $weights2 = [6,5,4,3,2,9,8,7,6,5,4,3,2];

    $calc = static function (string $value, array $weights): int {
        $sum = 0;
        foreach ($weights as $i => $weight) {
            $sum += ((int) $value[$i]) * $weight;
        }
        $rest = $sum % 11;
        return $rest < 2 ? 0 : 11 - $rest;
    };

    return $calc($digits, $weights1) === (int) $digits[12]
        && $calc($digits, $weights2) === (int) $digits[13];
}

function pix_normalize_key(string $key): string
{
    $key = trim($key);

    if ($key === '') {
        return '';
    }

    // E-mail e chave aleatória devem ser enviados sem formatação adicional.
    if (str_contains($key, '@')) {
        return strtolower($key);
    }

    if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $key)) {
        return strtolower($key);
    }

    $digits = preg_replace('/\D+/', '', $key) ?: '';

    // CPF e CNPJ: somente dígitos.
    if (strlen($digits) === 11 && pix_is_valid_cpf($digits)) {
        return $digits;
    }

    if (strlen($digits) === 14 && pix_is_valid_cnpj($digits)) {
        return $digits;
    }

    // Telefone Pix usa o padrão E.164. Para número brasileiro, +55 + DDD + celular.
    if (strlen($digits) === 13 && str_starts_with($digits, '55') && preg_match('/^55[1-9][0-9]9[0-9]{8}$/', $digits)) {
        return '+' . $digits;
    }

    if (strlen($digits) === 11 && preg_match('/^[1-9][0-9]9[0-9]{8}$/', $digits)) {
        return '+55' . $digits;
    }

    // Para CPF/CNPJ digitados com pontuação, mesmo se inválidos, evita levar pontuação ao BR Code.
    if (in_array(strlen($digits), [11, 14], true)) {
        return $digits;
    }

    return $key;
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
    $key = pix_normalize_key($key);

    if ($key === '' || strlen($key) > 77) {
        throw new RuntimeException('A chave Pix configurada é inválida.');
    }

    $merchant = pix_field('00', 'br.gov.bcb.pix') . pix_field('01', $key);

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
