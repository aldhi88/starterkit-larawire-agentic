<?php

namespace Aldhi88\StarterKit\Services\Starter;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use InvalidArgumentException;

class TwoFactorAuthenticationService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private const RECOVERY_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function enabled(): bool
    {
        return (bool) config('starter.auth.login_two_factor_enabled', true);
    }

    public function generateSecret(): string
    {
        return $this->base32Encode(random_bytes(20));
    }

    public function provisioningUri(string $secret, string $accountName): string
    {
        $issuer = trim((string) config('app.name', 'Aplikasi')) ?: 'Aplikasi';
        $label = rawurlencode($issuer.':'.$accountName);

        return 'otpauth://totp/'.$label.'?'.http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => 6,
            'period' => 30,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function qrCodeDataUri(string $secret, string $accountName): string
    {
        $qrCode = Encoder::encode(
            $this->provisioningUri($secret, $accountName),
            ErrorCorrectionLevel::M(),
        );
        $matrix = $qrCode->getMatrix();
        $margin = 4;
        $size = $matrix->getWidth() + ($margin * 2);
        $path = '';

        for ($y = 0; $y < $matrix->getHeight(); $y++) {
            $runStart = null;

            for ($x = 0; $x <= $matrix->getWidth(); $x++) {
                $filled = $x < $matrix->getWidth() && $matrix->get($x, $y) === 1;

                if ($filled && $runStart === null) {
                    $runStart = $x;
                }

                if (! $filled && $runStart !== null) {
                    $runWidth = $x - $runStart;
                    $path .= 'M'.($runStart + $margin).' '.($y + $margin).'h'.$runWidth.'v1h-'.$runWidth.'z';
                    $runStart = null;
                }
            }
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$size.' '.$size.'" shape-rendering="crispEdges">'
            .'<rect width="100%" height="100%" fill="#fff"/>'
            .'<path d="'.$path.'" fill="#111827"/>'
            .'</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    public function verifyCode(string $secret, string $code, ?int $timestamp = null): bool
    {
        $code = preg_replace('/\D/', '', $code) ?? '';

        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $counter = intdiv($timestamp ?? time(), 30);

        foreach ([-1, 0, 1] as $window) {
            if (hash_equals($this->codeAtCounter($secret, $counter + $window), $code)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];

        while (count($codes) < $count) {
            $raw = '';

            for ($index = 0; $index < 8; $index++) {
                $raw .= self::RECOVERY_ALPHABET[random_int(0, strlen(self::RECOVERY_ALPHABET) - 1)];
            }

            $code = substr($raw, 0, 4).'-'.substr($raw, 4);

            if (! in_array($code, $codes, true)) {
                $codes[] = $code;
            }
        }

        return $codes;
    }

    /** @param list<string> $codes
     * @return list<string>
     */
    public function hashRecoveryCodes(array $codes): array
    {
        return array_map(fn (string $code): string => $this->hashRecoveryCode($code), $codes);
    }

    /**
     * @param  list<string>  $hashedCodes
     * @return list<string>|null
     */
    public function consumeRecoveryCode(string $candidate, array $hashedCodes): ?array
    {
        $candidateHash = $this->hashRecoveryCode($candidate);

        foreach ($hashedCodes as $index => $hashedCode) {
            if (hash_equals($hashedCode, $candidateHash)) {
                unset($hashedCodes[$index]);

                return array_values($hashedCodes);
            }
        }

        return null;
    }

    public function formattedSecret(string $secret): string
    {
        return trim(chunk_split($secret, 4, ' '));
    }

    private function codeAtCounter(string $secret, int $counter): string
    {
        $key = $this->base32Decode($secret);
        $binaryCounter = pack('N2', ($counter >> 32) & 0xFFFFFFFF, $counter & 0xFFFFFFFF);
        $hash = hash_hmac('sha1', $binaryCounter, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($value % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    private function base32Encode(string $bytes): string
    {
        $buffer = 0;
        $bitsLeft = 0;
        $encoded = '';

        foreach (unpack('C*', $bytes) ?: [] as $byte) {
            $buffer = ($buffer << 8) | $byte;
            $bitsLeft += 8;

            while ($bitsLeft >= 5) {
                $bitsLeft -= 5;
                $encoded .= self::BASE32_ALPHABET[($buffer >> $bitsLeft) & 31];
            }
        }

        if ($bitsLeft > 0) {
            $encoded .= self::BASE32_ALPHABET[($buffer << (5 - $bitsLeft)) & 31];
        }

        return $encoded;
    }

    private function base32Decode(string $encoded): string
    {
        $encoded = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $encoded) ?? '');
        $buffer = 0;
        $bitsLeft = 0;
        $decoded = '';

        foreach (str_split($encoded) as $character) {
            $value = strpos(self::BASE32_ALPHABET, $character);

            if ($value === false) {
                throw new InvalidArgumentException('Secret authenticator tidak valid.');
            }

            $buffer = ($buffer << 5) | $value;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $decoded .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $decoded;
    }

    private function hashRecoveryCode(string $code): string
    {
        $normalized = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $code) ?? '');

        return hash_hmac('sha256', $normalized, (string) config('app.key'));
    }
}
