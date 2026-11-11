<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Notifikasi Tiket Helpdesk IT</title>
</head>
<body style="margin:0;padding:24px;background-color:#F8FAFC;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0F172A;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width:560px;margin:0 auto;background:#FFFFFF;border:1px solid #E2E8F0;border-radius:14px;overflow:hidden;">
        <tr>
            <td style="background:#1E40AF;padding:18px 24px;color:#FFFFFF;">
                <div style="font-size:15px;font-weight:700;letter-spacing:0.2px;">NALA — Helpdesk IT</div>
                <div style="font-size:12px;color:#DBEAFE;margin-top:2px;">Pembaruan Status Permohonan Tiket</div>
            </td>
        </tr>
        <tr>
            <td style="padding:24px;">
                <p style="margin:0 0 12px;font-size:14px;line-height:1.6;">
                    Halo <strong>{{ $recipientName }}</strong>,
                </p>
                <p style="margin:0 0 16px;font-size:13px;color:#475569;line-height:1.6;">
                    Terdapat pembaruan status pada tiket permohonan layanan IT berikut yang membutuhkan perhatian atau informasi Anda:
                </p>

                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:14px 16px;margin-bottom:18px;font-size:13px;">
                    <tr>
                        <td style="padding:4px 0;color:#64748B;width:130px;">Nomor Tiket</td>
                        <td style="padding:4px 0;font-weight:700;color:#1E40AF;">{{ $ticket->ticket_number }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;color:#64748B;">Judul</td>
                        <td style="padding:4px 0;font-weight:600;color:#0F172A;">{{ $ticket->title }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;color:#64748B;">Kategori</td>
                        <td style="padding:4px 0;color:#334155;">{{ $ticket->category ?? 'Umum' }} ({{ $ticket->priority ?? 'Low' }})</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;color:#64748B;">Status Terbaru</td>
                        <td style="padding:4px 0;font-weight:700;color:#0F172A;">{{ $ticket->status }}</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;color:#64748B;">Oleh</td>
                        <td style="padding:4px 0;color:#334155;">{{ $actorName }}</td>
                    </tr>
                    @if(!empty($remarks))
                        <tr>
                            <td style="padding:6px 0 2px;color:#64748B;vertical-align:top;">Catatan</td>
                            <td style="padding:6px 0 2px;color:#334155;">{{ $remarks }}</td>
                        </tr>
                    @endif
                </table>

                <div style="text-align:left;margin-top:20px;">
                    <a href="{{ route('tickets.show', $ticket) }}" style="display:inline-block;background:#1E40AF;color:#FFFFFF;text-decoration:none;font-size:13px;font-weight:600;padding:10px 18px;border-radius:8px;">
                        Buka Rincian Tiket &rarr;
                    </a>
                </div>
            </td>
        </tr>
        <tr>
            <td style="padding:14px 24px;background:#F8FAFC;border-top:1px solid #F1F5F9;font-size:11px;color:#94A3B8;">
                Email ini dikirim secara otomatis oleh Sistem NALA Helpdesk IT.
            </td>
        </tr>
    </table>
</body>
</html>
