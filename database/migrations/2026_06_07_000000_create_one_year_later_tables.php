<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oyl_challenges', function (Blueprint $table) {
            $table->id();
            $table->string('sender_email');
            $table->string('sender_name')->nullable();
            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();
            $table->string('goal_title');
            $table->text('goal_description');
            $table->text('written_terms')->nullable();
            $table->string('video_storage_key');
            $table->date('delivery_date');
            $table->string('status', 20)->default('pending');
            $table->char('recipient_token_hash', 64)->unique();
            $table->text('recipient_token_encrypted');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'delivery_date']);
        });

        Schema::create('oyl_recipient_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->unique()->constrained('oyl_challenges')->cascadeOnDelete();
            $table->string('response', 20);
            $table->text('response_note')->nullable();
            $table->timestamps();
        });

        Schema::create('oyl_email_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->constrained('oyl_challenges')->cascadeOnDelete();
            $table->string('email_type', 30);
            $table->string('recipient_email');
            $table->timestamp('sent_at')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['challenge_id', 'email_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oyl_email_events');
        Schema::dropIfExists('oyl_recipient_responses');
        Schema::dropIfExists('oyl_challenges');
    }
};
