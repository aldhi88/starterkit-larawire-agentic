<?php

namespace Aldhi88\StarterKit\Services\Starter;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginHumanChallengeService
{
    private const SESSION_KEY = 'starter.login_human_challenge';

    private const EXPIRES_SECONDS = 600;

    private const MAX_FAILURES = 10;

    /** @var array<int, list<string>> */
    private const DIGIT_SEGMENTS = [
        0 => ['a', 'b', 'c', 'd', 'e', 'f'],
        1 => ['b', 'c'],
        2 => ['a', 'b', 'g', 'e', 'd'],
        3 => ['a', 'b', 'c', 'd', 'g'],
        4 => ['f', 'g', 'b', 'c'],
        5 => ['a', 'f', 'g', 'c', 'd'],
        6 => ['a', 'f', 'g', 'e', 'c', 'd'],
        7 => ['a', 'b', 'c'],
        8 => ['a', 'b', 'c', 'd', 'e', 'f', 'g'],
        9 => ['a', 'b', 'c', 'd', 'f', 'g'],
    ];

    /** @var array<string, array{int, int, int, int}> */
    private const SEGMENT_RECTS = [
        'a' => [7, 2, 22, 5],
        'b' => [28, 6, 5, 22],
        'c' => [28, 31, 5, 22],
        'd' => [7, 52, 22, 5],
        'e' => [3, 31, 5, 22],
        'f' => [3, 6, 5, 22],
        'g' => [7, 27, 22, 5],
    ];

    public function __construct(
        private readonly AuditLogService $auditLogs,
    ) {}

    public function enabled(): bool
    {
        return (bool) config('starter.auth.login_human_challenge_enabled', true);
    }

    public function issue(): string
    {
        if (! $this->enabled()) {
            $this->clear();

            return '';
        }

        if (! request()->hasSession()) {
            return '';
        }

        $code = $this->generateUniqueDigits();
        request()->session()->put(self::SESSION_KEY, [
            'hash' => $this->hash($code),
            'expires_at' => now()->addSeconds(self::EXPIRES_SECONDS)->timestamp,
        ]);

        return $this->renderSvgDataUri($code);
    }

    public function verify(string $answer): void
    {
        if (! $this->enabled()) {
            return;
        }

        $ipAddress = (string) request()->ip();
        $throttleKey = 'login-human-challenge:'.hash('sha256', $ipAddress);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_FAILURES)) {
            $this->auditLogs->recordSecurityEvent(
                'auth.login_human_challenge_blocked',
                'Validasi manusia dibatasi sementara',
                metadata: ['reason' => 'rate_limited'],
            );

            throw ValidationException::withMessages([
                'form.human_challenge' => 'Terlalu banyak jawaban yang salah. Coba lagi dalam '.RateLimiter::availableIn($throttleKey).' detik.',
            ]);
        }

        $challenge = request()->hasSession()
            ? request()->session()->get(self::SESSION_KEY)
            : null;
        $valid = is_array($challenge)
            && is_string($challenge['hash'] ?? null)
            && is_int($challenge['expires_at'] ?? null)
            && $challenge['expires_at'] >= now()->timestamp
            && preg_match('/^\d{5}$/', $answer) === 1
            && hash_equals($challenge['hash'], $this->hash($answer));

        if (! $valid) {
            RateLimiter::hit($throttleKey, self::EXPIRES_SECONDS);
            $this->clear();
            $this->auditLogs->recordSecurityEvent(
                'auth.login_human_challenge_failed',
                'Validasi manusia gagal',
                metadata: ['reason' => 'invalid_or_expired'],
            );

            throw ValidationException::withMessages([
                'form.human_challenge' => 'Angka keamanan tidak sesuai. Gunakan angka baru yang ditampilkan.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $this->clear();
    }

    private function clear(): void
    {
        if (request()->hasSession()) {
            request()->session()->forget(self::SESSION_KEY);
        }
    }

    private function generateUniqueDigits(): string
    {
        $digits = range(0, 9);

        for ($index = count($digits) - 1; $index > 0; $index--) {
            $swap = random_int(0, $index);
            [$digits[$index], $digits[$swap]] = [$digits[$swap], $digits[$index]];
        }

        if ($digits[0] === 0) {
            [$digits[0], $digits[1]] = [$digits[1], $digits[0]];
        }

        return implode('', array_slice($digits, 0, 5));
    }

    private function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }

    private function renderSvgDataUri(string $code): string
    {
        $width = 286;
        $height = 76;
        $groups = '';

        foreach (str_split($code) as $index => $digit) {
            $x = 18 + ($index * 52) + random_int(-2, 2);
            $y = 8 + random_int(-2, 2);
            $rotation = random_int(-7, 7);
            $segments = '';

            foreach (self::DIGIT_SEGMENTS[(int) $digit] as $segment) {
                [$segmentX, $segmentY, $segmentWidth, $segmentHeight] = self::SEGMENT_RECTS[$segment];
                $segments .= '<rect x="'.$segmentX.'" y="'.$segmentY.'" width="'.$segmentWidth.'" height="'.$segmentHeight.'" rx="2"/>';
            }

            $groups .= '<g transform="translate('.$x.' '.$y.') rotate('.$rotation.' 18 30)">'.$segments.'</g>';
        }

        $noise = '';

        for ($index = 0; $index < 6; $index++) {
            $noise .= '<path d="M'.random_int(0, 35).' '.random_int(8, 68)
                .' C'.random_int(70, 110).' '.random_int(0, 76).', '.random_int(170, 220).' '.random_int(0, 76).', '.random_int(250, 286).' '.random_int(8, 68).'"/>';
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$width.' '.$height.'" role="img" aria-label="Angka keamanan">'
            .'<rect width="100%" height="100%" rx="12" fill="#f8fafc"/>'
            .'<g fill="#172033">'.$groups.'</g>'
            .'<g fill="none" stroke="#94a3b8" stroke-width="1" opacity=".35">'.$noise.'</g>'
            .'</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
