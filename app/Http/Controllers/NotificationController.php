<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';
        $unreadCount = $request->user()->unreadNotifications()->count();
        $query = $filter === 'unread'
            ? $request->user()->unreadNotifications()
            : $request->user()->notifications();
        $notifications = $query->latest()->paginate(15)->withQueryString();

        return view('notifications.index', compact('notifications', 'filter', 'unreadCount'));
    }

    public function read(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        abort_unless(
            $notification->notifiable_type === $request->user()::class
            && (int) $notification->notifiable_id === (int) $request->user()->id,
            403,
        );

        $notification->markAsRead();

        $url = (string) ($notification->data['url'] ?? route('notifications.index'));

        return redirect()->to(str_starts_with($url, url('/')) ? $url : route('notifications.index'));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'Semua notifikasi telah ditandai dibaca.');
    }
}
