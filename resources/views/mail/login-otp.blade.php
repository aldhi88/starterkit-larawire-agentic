<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kode OTP login - {{ $brandName }}</title>
</head>
<body style="margin:0;background:#f5f5f5;color:#202124;font-family:Arial,sans-serif;line-height:1.6;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f5f5f5;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #dedede;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td align="center" style="padding:28px 32px 24px;border-bottom:1px solid #e8e8e8;">
                            @if (filled($brandLogoUrl))
                                <img src="{{ $brandLogoUrl }}" width="160" alt="{{ $brandName }}" style="display:block;width:auto;max-width:160px;height:auto;max-height:46px;margin:0 auto 12px;object-fit:contain;">
                            @endif
                            <div style="font-size:17px;font-weight:700;line-height:1.3;color:#202124;">{{ $brandName }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <h1 style="margin:0 0 12px;font-size:22px;line-height:1.35;color:#202124;">Verifikasi login Anda</h1>
                            <p style="margin:0 0 12px;">Halo {{ $recipientName }},</p>
                            <p style="margin:0 0 24px;color:#4b5563;">Masukkan kode berikut pada halaman login {{ $appName }}.</p>

                            <table role="presentation" cellspacing="0" cellpadding="0" align="center" style="margin:0 auto 24px;">
                                <tr>
                                    <td align="center" style="padding:14px 24px;border:1px solid #c9c9c9;border-radius:8px;background:#fafafa;color:#111827;font-family:Courier New,monospace;font-size:30px;font-weight:700;letter-spacing:6px;line-height:1.2;user-select:all;">{{ $otpCode }}</td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 22px;background:#fafafa;border:1px solid #e3e3e3;border-radius:8px;">
                                <tr>
                                    <td style="padding:14px 16px;color:#4b5563;font-size:13px;">
                                        Kode berlaku selama <strong style="color:#202124;">{{ $expiresInMinutes }} menit</strong> dan hanya dapat digunakan satu kali. Jangan berikan kode ini kepada siapa pun.
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0;color:#6b7280;font-size:13px;">Jika Anda tidak mencoba login, abaikan email ini dan segera hubungi administrator aplikasi.</p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:18px 32px;border-top:1px solid #e8e8e8;color:#8a8a8a;font-size:12px;">
                            Email keamanan otomatis dari {{ $brandName }}.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
