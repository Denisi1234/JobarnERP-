<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->unique(['message_id','user_id']);
            $table->index(['user_id','read_at']);
        });

        // Add sender index for rate limit + is_read already indexed
        Schema::table('messages', function (Blueprint $table) {
            $table->index('sender_id');
            $table->index('created_at');
        });

        // Backfill: for existing dept broadcasts, create pivot rows for all dept members as unread
        // and for direct messages, create row for recipient
        $messages = \App\Models\Message::all();
        foreach ($messages as $m) {
            if ($m->recipient_id) {
                \DB::table('message_user')->updateOrInsert(
                    ['message_id'=>$m->id,'user_id'=>$m->recipient_id],
                    ['read_at'=>$m->is_read ? $m->read_at : null, 'created_at'=>now(), 'updated_at'=>now()]
                );
            } elseif ($m->department_id) {
                $emails = \App\Models\Employee::where('department_id',$m->department_id)->pluck('email');
                $users = \App\Models\User::whereIn('email',$emails)->whereIn('role',['sales','reception','manager','it'])->get();
                foreach ($users as $u) {
                    if ($u->id === $m->sender_id) continue;
                    \DB::table('message_user')->updateOrInsert(
                        ['message_id'=>$m->id,'user_id'=>$u->id],
                        ['read_at'=> $m->is_read ? $m->read_at : null, 'created_at'=>now(), 'updated_at'=>now()]
                    );
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('message_user');
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['sender_id']);
            $table->dropIndex(['created_at']);
        });
    }
};
