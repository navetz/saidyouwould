<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oyl_challenges', function (Blueprint $table) {
            $table->string('mode', 20)->default('friend')->after('sender_name');
            $table->boolean('notify_recipient')->default(true)->after('recipient_name');
            $table->string('payment_status', 20)->default('unpaid')->after('status');
            $table->timestamp('paid_at')->nullable()->after('payment_status');
            $table->string('stripe_session_id')->nullable()->after('paid_at');
            $table->unsignedInteger('amount_cents')->nullable()->after('stripe_session_id');
            $table->string('currency', 3)->nullable()->after('amount_cents');

            $table->index('stripe_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('oyl_challenges', function (Blueprint $table) {
            $table->dropIndex(['stripe_session_id']);
            $table->dropColumn([
                'mode',
                'notify_recipient',
                'payment_status',
                'paid_at',
                'stripe_session_id',
                'amount_cents',
                'currency',
            ]);
        });
    }
};
