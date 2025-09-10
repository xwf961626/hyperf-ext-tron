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
        Schema::create('apis', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100);
            $table->string('url', 200);
            $table->string('api_key', 200);
            $table->string('api_secret', 200)->nullable();
            $table->string('callback_url', 200)->nullable();
            $table->decimal('balance', 20, 6)->default(0);
            $table->bigInteger('price')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('weight')->default(0);
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apis');
    }
};
