<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GlobalSearchController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $results = [];
        $like = '%' . $q . '%';

        try {
            // Visitors — from visits table (last 8)
            $visits = Visit::with('employee')
                ->where(function ($w) use ($like) {
                    $w->where('visitor', 'ILIKE', $like)
                      ->orWhere('visitor_phone', 'ILIKE', $like)
                      ->orWhere('visitor_email', 'ILIKE', $like)
                      ->orWhere('purpose', 'ILIKE', $like);
                })
                ->latest('arrival')->limit(6)->get();

            foreach ($visits as $v) {
                $results[] = [
                    'type' => 'visitor',
                    'icon' => 'badge',
                    'title' => $v->visitor,
                    'subtitle' => trim(($v->visitor_phone ? $v->visitor_phone . ' • ' : '') . ($v->purpose ?? '')),
                    'meta' => $v->arrival ? $v->arrival->format('Y-m-d H:i') : $v->created_at->format('Y-m-d'),
                    'url' => route('reception.visitors') . '?search=' . urlencode($q),
                    'status' => $v->departure ? 'Checked-out' : 'On-site',
                ];
            }

            // Employees — directory
            $emps = Employee::where(function ($w) use ($like) {
                    $w->where('first_name', 'ILIKE', $like)
                      ->orWhere('last_name', 'ILIKE', $like)
                      ->orWhere('email', 'ILIKE', $like);
                })->with(['designation','department'])->limit(5)->get();

            foreach ($emps as $e) {
                $results[] = [
                    'type' => 'employee',
                    'icon' => 'contacts',
                    'title' => $e->full_name,
                    'subtitle' => trim(($e->designation->name ?? '') . ' • ' . ($e->department->name ?? ''), ' •'),
                    'meta' => $e->email,
                    'url' => route('reception.directory') . '?search=' . urlencode($q),
                ];
            }

        } catch (\Throwable $e) {
            Log::warning('[Search] DB error', ['q' => $q, 'error' => $e->getMessage()]);
            return response()->json(['results' => [], 'error' => 'Search offline']);
        }

        // Limit total to 8 for dropdown
        return response()->json(['results' => array_slice($results, 0, 8)]);
    }
}
