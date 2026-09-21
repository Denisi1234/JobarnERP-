<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Message;
use App\Models\User;
use App\Notifications\MessageReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    // Inbox - each portal own inbox (sales/reception/manager/it) — 4 portals only, real chat — HARDENED
    public function index(Request $r)
    {
        $user = auth()->user();
        $portal = match(true){
            $r->routeIs('reception.*') || $r->is('reception/*') => 'Reception',
            $r->routeIs('it.*') || $r->is('it/*') => 'IT',
            $r->routeIs('sales.*') || $r->is('sales/*') => 'Sales',
            $r->routeIs('manager.*') || $r->is('manager/*') => 'Manager',
            default => $user->getRoleLabel(),
        };
        // Hardening: portal isolation — user role must match portal
        $expected = match($portal){ 'Reception'=>'reception','IT'=>'it','Sales'=>'sales','Manager'=>'manager', default=>null };
        if($expected && $user->role !== $expected) abort(403, 'This inbox belongs to '.$portal.' portal only');
        // For real chat: include both sent and received + dept broadcasts
        $deptIds = Employee::where('email', $user->email)->pluck('department_id')->filter()->all();
        if(empty($deptIds)){
            $map = ['reception'=>'Front Desk Operations','it'=>'Information Technology','sales'=>'Sales & Business Dev','manager'=>'Administration'];
            $n = $map[$user->role ?? ''] ?? null;
            if($n){
                $d = Department::whereRaw('LOWER(name) = ?', [strtolower($n)])->first()
                  ?? Department::where('name','like','%'.explode(' ', $n)[0].'%')->first();
                if($d) $deptIds = [$d->id];
            }
        }
        $all = Message::with(['sender','recipient','department'])
            ->where(function($q) use ($user, $deptIds){
                $q->where('sender_id', $user->id)
                  ->orWhere('recipient_id', $user->id)
                  ->orWhere(function($dq) use ($deptIds){
                      if($deptIds) $dq->whereIn('department_id', $deptIds)->whereNull('recipient_id');
                  });
            })
            ->latest()->take(100)->get();
        $messages = Message::with(['sender','department'])->forUser($user)->latest()->paginate(30);
        // Build conversations for real chat sidebar
        $conversations = $all->groupBy(function($m) use ($user){
            if($m->sender_id == $user->id){
                return $m->recipient_id ? 'user:'.$m->recipient_id : 'dept:'.$m->department_id;
            } else {
                return 'user:'.$m->sender_id;
            }
        })->map(function($msgs) use ($user){
            $last = $msgs->first();
            $other = $last->sender_id == $user->id ? ($last->recipient ?? $last->department) : $last->sender;
            $name = $other?->name ?? $last->recipient_handle ?? ($last->department?->name ?? 'Unknown');
            if($last->department && !$last->recipient_id) $name = $last->department->name.' • Channel';
            return [
                'key' => $msgs->first()->id.'-'.$name,
                'name' => $name,
                'handle' => $last->recipient_handle ?? ($other?->email ?? ''),
                'last' => $last,
                'count' => $msgs->count(),
                'unread' => $msgs->filter(fn($m)=> $m->recipient_id == $user->id ? !$m->is_read : \DB::table('message_user')->where('message_id',$m->id)->where('user_id',$user->id)->whereNull('read_at')->exists())->count(),
                'msgs' => $msgs->sortBy('created_at')->values(),
            ];
        })->sortByDesc(fn($c)=> $c['last']->created_at)->values();
        $conversationsJson = $conversations->map(fn($c)=>[
            'name'=>$c['name'],
            'handle'=>$c['handle'],
            'msgs'=>$c['msgs']->map(fn($m)=>[
                'body'=>$m->body,
                'from'=> $m->sender?->name ?? 'Unknown',
                'mine'=> $m->sender_id == $user->id,
                'time'=> $m->created_at->format('H:i'),
                'date'=> $m->created_at->format('d M'),
            ])->values()->toArray(),
        ])->values()->toArray();
        return view('messages.index', compact('messages','portal','all','conversations','conversationsJson'));
    }

    public function unreadCount()
    {
        $user = auth()->user();
        $c = \DB::table('message_user')->where('user_id', $user->id)->whereNull('read_at')->count();
        // Fallback for direct messages not yet pivoted (legacy)
        if($c===0) $c = Message::forUser($user)->where('is_read', false)->count();
        return response()->json(['unread' => $c]);
    }

    public function store(Request $r)
    {
        $r->validate([
            'recipient_handle' => 'nullable|string|max:100|regex:/^[@#a-zA-Z0-9._\-\s]+$/',
            'recipient_name' => 'nullable|string|max:255|regex:/^[a-zA-Z0-9\s\.\-—_]+$/',
            'recipient_id' => 'nullable|integer|exists:users,id',
            'department_id' => 'nullable|integer|exists:departments,id',
            'body' => 'required|string|min:1|max:2000',
        ]);
        $sender = auth()->user();
        // Hardening: idempotency — same body to same target within 30s
        $dup = Message::where('sender_id', $sender->id)->where('body', trim(strip_tags($r->body)))->where('created_at','>', now()->subSeconds(30))->latest()->first();
        if($dup && $dup->recipient_handle === trim($r->recipient_handle ?? '') && $dup->recipient_id == ($r->recipient_id ?? null)) {
            return response()->json(['success'=>true,'message'=>'Message already sent','id'=>$dup->id, 'duplicate'=>true]);
        }
        // Hardening: rate limit 30/min per sender
        $recent = Message::where('sender_id', $sender->id)->where('created_at', '>', now()->subMinute())->count();
        if($recent >= 30) abort(429, 'Too many messages — please wait');
        // Hardening: sanitize body — strip tags, trim, prevent empty after strip
        $rawBody = trim($r->body);
        $cleanBody = trim(strip_tags($rawBody));
        if(mb_strlen($cleanBody) < 1) abort(422, 'Message cannot be empty');
        if(mb_strlen($cleanBody) > 2000) $cleanBody = mb_substr($cleanBody, 0, 2000);
        // Resolve recipient: prefer explicit user, then handle (@name), then name fuzzy
        $recipient = null;
        $deptId = $r->department_id;
        $handle = $r->recipient_handle;
        $name = trim($r->recipient_name ?? '');
        if($r->recipient_id) $recipient = User::find($r->recipient_id);
        if(!$recipient && $handle){
            $h = ltrim(strtolower($handle), '@');
            $hDot = str_replace('.', ' ', $h);
            $recipient = User::whereRaw("LOWER(REPLACE(name,' ','.')) = ?", [$h])->first()
                ?? User::where('email', 'like', $h.'@%')->first()
                ?? User::whereRaw('LOWER(name) = ?', [strtolower($hDot)])->first();
            if(!$recipient){
                $emp = Employee::whereRaw("LOWER(CONCAT(first_name,'.',last_name)) = ?", [$h])->first()
                    ?? Employee::whereRaw("LOWER(CONCAT(first_name,' ',last_name)) = ?", [strtolower($hDot)])->first();
                if($emp) $recipient = User::where('email', $emp->email)->first();
            }
        }
        if(!$recipient && $name){
            $recipient = User::where('name', $name)->first()
                ?? User::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
            if(!$recipient){
                $emp = Employee::whereRaw("LOWER(CONCAT(first_name,' ',last_name)) = ?", [strtolower($name)])->first();
                if($emp) $recipient = User::where('email', $emp->email)->first();
            }
        }
        // Fallback department via employee/recipient
        if(!$deptId && $recipient){
            $emp = Employee::where('email', $recipient->email)->first();
            if($emp) $deptId = $emp->department_id;
        }
        if(!$deptId && $handle && str_starts_with(strtolower($handle), '#dept-')){
            $slug = substr(strtolower($handle), 6);
            $dept = Department::all()->first(fn($d)=> strtolower(preg_replace('/[^a-z0-9]/','',$d->name))===$slug);
            if($dept) $deptId = $dept->id;
        }
        if(!$recipient && !$deptId){
            // last resort: try department by name channel
            if($name && str_contains(strtolower($name), 'channel')){
                $dn = trim(str_ireplace('channel','',$name));
                $dept = Department::whereRaw('LOWER(name) = ?', [strtolower($dn)])->first();
                if($dept) $deptId = $dept->id;
            }
        }

        $cleanHandle = $handle ? trim(strip_tags($handle)) : null;
        // Resolve targets before transaction for pivot
        $targets = collect();
        if($recipient) $targets->push($recipient);
        elseif($deptId){
            $emails = Employee::where('department_id', $deptId)->pluck('email');
            $targets = User::whereIn('email', $emails)->whereIn('role', ['sales','reception','manager','it'])->get();
        }
        $targets = $targets->filter(fn($u)=> $u && $u->id !== $sender->id)->unique('id');
        $msg = \Illuminate\Support\Facades\DB::transaction(function() use ($sender, $recipient, $deptId, $cleanHandle, $cleanBody, $targets){
            $m = Message::create([
                'uuid' => (string) Str::uuid(),
                'sender_id' => $sender->id,
                'recipient_id' => $recipient?->id,
                'department_id' => $deptId,
                'recipient_handle' => $cleanHandle,
                'body' => $cleanBody,
            ]);
            // Pivot per-user read state — fixes global is_read bug
            foreach($targets as $u){
                try { \DB::table('message_user')->insert(['message_id'=>$m->id,'user_id'=>$u->id,'created_at'=>now(),'updated_at'=>now()]); } catch(\Throwable $e){}
            }
            // Audit: lightweight timeline entry
            try { \App\Models\VisitTimeline::create(['visit_id'=>null,'user_id'=>$sender->id,'event_type'=>'message','title'=>'Message: '.$sender->name.' → '.($recipient?->name ?? 'Dept'),'description'=>\Str::limit($cleanBody,80)]); } catch(\Throwable $e){}
            return $m;
        });

        foreach($targets as $u) $u->notify(new MessageReceived($msg));

        return response()->json(['success'=>true,'message'=>'Message sent','id'=>$msg->id]);
    }

    public function markRead($id)
    {
        $msg = Message::forUser(auth()->user())->findOrFail($id);
        \DB::table('message_user')->updateOrInsert(['message_id'=>$msg->id,'user_id'=>auth()->id()], ['read_at'=>now(),'updated_at'=>now(), 'created_at'=>now()]);
        $msg->update(['is_read'=>true,'read_at'=>now()]);
        return back();
    }

    public function markAllRead()
    {
        $user = auth()->user();
        $ids = Message::forUser($user)->pluck('id');
        foreach($ids as $mid){
            \DB::table('message_user')->updateOrInsert(['message_id'=>$mid,'user_id'=>$user->id], ['read_at'=>now(),'updated_at'=>now(),'created_at'=>now()]);
        }
        Message::forUser($user)->where('is_read', false)->update(['is_read'=>true,'read_at'=>now()]);
        return back();
    }
}
