<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oyl_challenges', function (Blueprint $table) {
            $table->boolean('anonymous')->default(false)->after('notify_recipient');
        });
    }

    public function down(): void
    {
        Schema::table('oyl_challenges', function (Blueprint $table) {
            $table->dropColumn('anonymous');
        });
    }
};
