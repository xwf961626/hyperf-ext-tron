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
        Schema::create('limit_resource_addresses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 200)->nullable();
            $table->string('address', 120)->index();
            $table->enum('resource', ['ENERGY', 'BANDWIDTH'])->default('ENERGY')->comment('地址资源类型：ENERGY-能量，BANDWIDTH-带宽');
            $table->bigInteger('current_quantity')->default(0)->comment('地址当前可用资源数量');
            $table->bigInteger('total_quantity')->default(0)->comment('当前地址总资源数量');
            $table->bigInteger('min_quantity')->default(0)->comment('最小资源数量阈值');
            $table->bigInteger('send_quantity')->default(0)->comment('达到最小资源数量阈值时，发送资源数量');
            $table->tinyInteger('status')->default(0)->comment('地址状态：0-关闭，1-开启');
            $table->integer('send_times')->default(0)->comment('已发送次数');
            $table->integer('max_times')->default(0)->comment('已发送次数最大阈值，达到后自动关闭地址');
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('limit_resource_addresses');
    }
};
