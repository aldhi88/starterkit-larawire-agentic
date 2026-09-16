<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $passwordWasReset ? 'Password sementara baru' : 'Akun baru' }} - {{ $appName }}</title>
</head>
<body style="margin:0;background:#f4f6f8;color:#1f2937;font-family:Arial,sans-serif;line-height:1.6;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f6f8;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;">
                    <tr>
                        <td style="padding:20px 32px;border-bottom:1px solid #e5e7eb;font-size:18px;font-weight:700;color:#111827;">
                            {{ $appName }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:#111827;">
                                {{ $passwordWasReset ? 'Password sementara baru' : 'Akun Anda telah dibuat' }}
                            </h1>

                            <p style="margin:0 0 16px;">Halo {{ $recipientName }},</p>
                            <p style="margin:0 0 20px;">
                                @if ($passwordWasReset)
                                    Administrator telah mereset password akun Anda.
                                @else
                                    Administrator telah membuat akun untuk Anda.
                                @endif
                                Gunakan kredensial sementara berikut untuk login.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 20px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;">
                                <tr>
                                    <td style="padding:18px 20px;">
                                        <div style="margin-bottom:8px;"><strong>Username:</strong> <span style="font-family:monospace;">{{ $username }}</span></div>
                                        <div><strong>Password sementara:</strong> <span style="font-family:monospace;">{{ $temporaryPassword }}</span></div>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 20px;">Anda wajib mengganti password setelah login pertama.</p>
                            <p style="margin:0 0 24px;">
                                <a href="{{ $loginUrl }}" style="display:inline-block;padding:11px 18px;background:#2563eb;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;">Buka Halaman Login</a>
                            </p>
                            <p style="margin:0;color:#6b7280;font-size:13px;">Jika Anda tidak mengenali aktivitas ini, segera hubungi administrator aplikasi.</p>
                            <p style="margin:12px 0 0;color:#9ca3af;font-size:12px;">Email otomatis dari {{ $appName }}.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
