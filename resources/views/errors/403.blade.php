<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 Akses Dibatasi — NALA Helpdesk IT</title>
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased bg-[#F8FAFC] text-slate-900 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl border border-slate-200/90 p-7 text-center shadow-xs">
        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-[#1E40AF] mx-auto flex items-center justify-center mb-4 font-bold text-base">
            403
        </div>
        <h1 class="text-lg font-bold text-slate-900">Akses Tidak Diizinkan</h1>
        <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
            {{ $exception->getMessage() ?: 'Anda tidak memiliki hak akses ke halaman atau tindakan ini sesuai role akun Anda.' }}
        </p>
        <div class="mt-6 flex items-center justify-center gap-2.5">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/dashboard') }}" class="btn-secondary text-xs py-2 px-4">
                Kembali
            </a>
            <a href="{{ url('/dashboard') }}" class="btn-primary text-xs py-2 px-4">
                Ke Beranda
            </a>
        </div>
    </div>
</body>
</html>
