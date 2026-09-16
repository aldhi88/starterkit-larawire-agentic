{{ $appName }}
{{ $passwordWasReset ? 'Password sementara baru' : 'Akun Anda telah dibuat' }}

Halo {{ $recipientName }},

@if ($passwordWasReset)
Administrator telah mereset password akun Anda.
@else
Administrator telah membuat akun untuk Anda.
@endif

Gunakan kredensial sementara berikut untuk login:

Username: {{ $username }}
Password sementara: {{ $temporaryPassword }}

Anda wajib mengganti password setelah login pertama.

Halaman login: {{ $loginUrl }}

Jika Anda tidak mengenali aktivitas ini, segera hubungi administrator aplikasi.

Email otomatis dari {{ $appName }}.
