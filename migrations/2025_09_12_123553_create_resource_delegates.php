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
        Schema::create('resource_delegates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->default(0);
            $table->bigInteger('quantity')->default(0)->comment('代理的资源数量');

            $table->tinyInteger('status')->default(0)->comment('代理资源状态，1成功，0待加，-1失败');
            $table->text('fail_reason')->nullable()->comment('失败原因');

            $table->text('tx_id')->nullable()->comment('链上id');
            $table->text('response_text')->nullable();

            $table->string('source', 30)->default(0)->comment('自定义业务类型');
            $table->text('source_info')->nullable()->comment('来源信息');

            $table->string('address', 156)->default('')->comment('接收地址');
            $table->string('from_address', 156)->default('')->comment('发送地址');
            $table->string('time', 16)->comment('代理时长');
            $table->enum('resource', ['ENERGY', 'BANDWIDTH'])->comment('资源类型: ENERGY-能量，BANDWIDTH-带宽');

            $table->string('api', 60)->default('pool')->comment('资源接口类型：pool-默认使用自有能量池');

            $table->decimal('lock_amount', 20, 6)->nullable()->comment('锁定金额trx');
            $table->integer('lock_duration')->nullable()->comment('锁定时长');

            $table->unsignedBigInteger('resource_address_id')->nullable()->comment('资源地址id');

            $table->decimal('price', 20, 6)->nullable()->comment('供应商单价SUN');
            $table->timestamp('delegate_at')->nullable()->comment('代理时间');
            $table->timestamp('undelegate_at')->nullable()->comment('回收时间');
            $table->timestamp('expired_dt')->nullable()->comment('预计回收时间');
            $table->string('undelegate_hash', 64)->nullable()->comment('回收hash');
            $table->tinyInteger('undelegate_status')->default(0)->comment('回收状态: 0-待回收，1-回收成功，2-回收失败');
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_delegates');
    }
};
