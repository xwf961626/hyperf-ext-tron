<?php
use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_resource_addresses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 129)->default('');
            $table->string('address', 129)->default('');
            $table->string('mode', 30)->default('');
            $table->string('type', 30)->default('');
            $table->integer('weight')->default(0);
            $table->string('approve_status', 30)->default('');
            $table->string('status', 30)->default('');
            $table->decimal('balance', 20, 6)->default(0);
            $table->bigInteger('energy_limit')->default(0);
            $table->bigInteger('energy')->default(0);
            $table->bigInteger('max_delegate_energy')->default(0);
            $table->bigInteger('bandwidth_limit')->default(0);
            $table->bigInteger('bandwidth')->default(0);
            $table->bigInteger('max_delegate_bandwidth')->default(0);
            $table->bigInteger('free_bandwidth')->default(0);
            $table->bigInteger('power_limit')->default(0);
            $table->bigInteger('power')->default(0);
            $table->integer('permission')->default(0);
            $table->text('config')->nullable();
            $table->datetimes();
            $table->timestamp('last_delegate_at')->nullable();
            $table->string('sort_num')->default('');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_addresses');
    }
};