<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function read($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        $data = $notification->data ?? [];

        // 1. Direct explicit URL
        if (!empty($data['url'])) {
            return redirect($data['url']);
        }

        // 2. IT Ticket ID
        if (!empty($data['ticket_id'])) {
            if (Route::has('it.tickets.show')) {
                return redirect()->route('it.tickets.show', $data['ticket_id']);
            }
            return redirect()->route('it.index');
        }

        // 3. Work Report / Logbook
        if (!empty($data['work_report_id'])) {
            $role = auth()->user()->role ?? 'reception';
            if (in_array($role, ['manager', 'admin']) && Route::has('manager.logbooks')) {
                return redirect()->route('manager.logbooks');
            }
            if (Route::has($role . '.logbook')) {
                return redirect()->route($role . '.logbook');
            }
        }

        // 4. Task ID
        $taskId = $data['task_id'] ?? null;
        if ($taskId) {
            $role = auth()->user()->role ?? 'reception';
            $route = match ($role) {
                'sales' => 'sales.tasks.show',
                'manager', 'admin' => 'manager.tasks.show',
                default => 'reception.tasks.show',
            };
            if (Route::has($route)) {
                return redirect()->route($route, $taskId);
            }
        }

        return redirect()->back();
    }

    public function readAll()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return redirect()->back()->with('success', 'All notifications marked as read');
    }

    public function liveHeader()
    {
        $user = auth()->user();
        $unread = $user ? $user->unreadNotifications()->count() : 0;
        $recent = $user ? $user->notifications()->latest()->take(6)->get()->map(function ($n) {
            $kind = $n->data['kind'] ?? 'system';
            return [
                'id' => $n->id,
                'title' => $n->data['title'] ?? 'Notification',
                'message' => $n->data['message'] ?? '',
                'kind' => $kind,
                'isUnread' => is_null($n->read_at),
                'time' => $n->created_at->diffForHumans(),
                'url' => route('notifications.read', $n->id),
            ];
        }) : collect();

        // Real working counts for header badge (pending IT, sales queue, tasks)
        $pendingIt = 0;
        $salesQueue = 0;
        $pendingTasks = 0;
        try {
            $pendingIt = \App\Models\ItTicket::whereIn('status', ['pending','new','assigned','in_progress'])->count();
            if ($pendingIt === 0) {
                $pendingIt = \App\Models\VisitService::where('department','IT')->whereIn('status',['pending','in_progress'])->count();
            }
            $salesQueue = \App\Models\Visit::whereNull('departure')->where(function($q){
                $q->where('current_department','sales')->orWhereHas('services', fn($sq)=>$sq->where('department','SALES'));
            })->count();
            $pendingTasks = \App\Models\Task::where('assigned_to', $user?->id)->whereNotIn('status',['completed','verified','closed','cancelled'])->count();
        } catch (\Throwable $e) {}

        $unreadMessages = 0; try { $unreadMessages = \App\Models\Message::forUser($user)->where('is_read', false)->count(); } catch(\Throwable $e){}
        $totalBadge = $unread + $pendingIt + ($salesQueue > 0 ? 1 : 0) + $unreadMessages;

        return response()->json([
            'unread' => $unread,
            'unreadMessages' => $unreadMessages,
            'totalBadge' => $totalBadge,
            'recent' => $recent,
            'counts' => [
                'pendingIt' => $pendingIt,
                'salesQueue' => $salesQueue,
                'pendingTasks' => $pendingTasks,
                'unreadMessages' => $unreadMessages,
            ],
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
