<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tron_api_keys', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('api_key', 128);
            $table->enum('type', ['node', 'scan'])->default('node')->comment('node节点接口的apiKey,scan网页查询的apiKey');
            $table->enum('status', ['active', 'floodwait', 'invalid'])->default('active')
                ->comment('状态：actvie正常，floodwait频率限制,invalid已失效');
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tron_api_keys');
    }
};
