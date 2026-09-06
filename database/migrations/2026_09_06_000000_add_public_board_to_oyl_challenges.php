<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oyl_challenges', function (Blueprint $table) {
            $table->boolean('is_public')->default(false)->after('anonymous');
            $table->string('public_slug', 16)->nullable()->unique()->after('is_public');
            $table->string('source', 60)->nullable()->after('public_slug');

            $table->index(['is_public', 'status', 'delivery_date']);
        });
    }

    public function down(): void
    {
        Schema::table('oyl_challenges', function (Blueprint $table) {
            $table->dropIndex(['is_public', 'status', 'delivery_date']);
            $table->dropColumn(['is_public', 'public_slug', 'source']);
        });
    }
};
