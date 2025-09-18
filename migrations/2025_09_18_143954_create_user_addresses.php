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
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('address', 129)->comment('用户地址')->unique();
            $table->enum('type', ['white', 'black'])->default('white')->comment('white白名单, black黑名单');
            $table->decimal('balance', 20,6)->comment('余额')->default(0);
            $table->tinyInteger('is_staking')->default(0)->comment('是否质押');
            $table->dateTime('active_at')->nullable()->comment('激活时间');
            $table->decimal('avg_spending')->default(0)->comment('平均支出');
            $table->bigInteger('remain_energy')->default(0)->comment('地址剩余能量');
            $table->tinyInteger('is_meet_conditions')->default(0)->comment('满足条件');
            $table->text('reason')->nullable()->comment('原因');
            $table->integer('total_buy')->default(0)->comment('累计购买次数');
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
