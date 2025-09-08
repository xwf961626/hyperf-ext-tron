<?php

use Hyperf\Database\Schema\Schema;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('energy_logs', function (Blueprint $table) {
            $table->bigIncrements('id'); // 自增主键，原来是 int(11)，这里建议用 bigIncrements
            $table->bigInteger('user_id')->default(0);
            $table->bigInteger('power_count')->default(0);

            $table->tinyInteger('status')->default(0)->comment('添加能量状态，1成功，0待加，-1失败');
            $table->text('fail_reason')->nullable()->comment('失败原因');

            $table->datetimes(); // created_at, updated_at

            $table->text('tx_id')->nullable()->comment('链上id');
            $table->text('response_text')->nullable();

            $table->tinyInteger('source')->default(0)->comment('来源：0绑定，1笔数交易，2回收，3租赁,4闪租');
            $table->text('source_info')->nullable()->comment('来源信息');

            $table->string('address', 156)->default('')->comment('接收地址');
            $table->string('from_address', 156)->default('')->comment('发送地址');
            $table->string('time', 16)->comment('能量回收时间');

            $table->string('energy_policy', 60)->default('api')->comment('能量来源: api, owner');

            $table->decimal('lock_amount', 20, 6)->nullable()->comment('锁定金额trx');
            $table->integer('lock_duration')->nullable()->comment('锁定时长');

            $table->unsignedBigInteger('resource_address_id')->nullable()->comment('资源地址id');

            $table->decimal('price', 20, 6)->nullable()->comment('供应商单价SUN');

            $table->timestamp('undelegate_at')->nullable()->comment('回收时间');
            $table->timestamp('expired_dt')->nullable()->comment('预计回收时间');
            $table->string('undelegate_hash', 64)->nullable()->comment('回收hash');
            $table->tinyInteger('undelegate_status')->default(0)->comment('回收状态: 0-待回收，1-回收成功，2-回收失败');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('energy_logs');
    }
};