<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('limit_resource_addresses', function (Blueprint $table) {
            $table->timestamp('last_opened_at')->nullable()->comment('最近一次开启时间');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('limit_resource_addresses', function (Blueprint $table) {
            $table->dropColumn('last_opened_at');
        });
    }
};
