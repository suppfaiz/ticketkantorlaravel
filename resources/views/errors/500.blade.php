<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 Gangguan Sistem — NALA Helpdesk IT</title>
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased bg-[#F8FAFC] text-slate-900 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl border border-slate-200/90 p-7 text-center shadow-xs">
        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-rose-600 mx-auto flex items-center justify-center mb-4 font-bold text-base">
            500
        </div>
        <h1 class="text-lg font-bold text-slate-900">Terjadi Kendala Teknis</h1>
        <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
            Permintaan Anda belum dapat diproses saat ini. Silakan muat ulang halaman atau hubungi Tim IT bila kendala berlanjut.
        </p>
        <div class="mt-6 flex items-center justify-center gap-2.5">
            <a href="{{ url('/dashboard') }}" class="btn-primary text-xs py-2 px-4">
                Kembali ke Beranda
            </a>
        </div>
    </div>
</body>
</html>
