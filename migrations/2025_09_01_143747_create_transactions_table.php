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
        Schema::create('transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('hash', 66)->index()->comment('交易哈希');
            $table->string('type', 50)->comment('交易类型');
            $table->unsignedBigInteger('block_id')->comment('区块高度');
            $table->string('contract', 64)->nullable()->comment('合约地址或合约名称');
            $table->timestamp('transacted_at')->nullable()->comment('交易时间');
            $table->unsignedBigInteger('transacted_time')->comment('交易时间戳');
            $table->unsignedInteger('transacted_amount_decimals')->default(6)->comment('金额小数位');
            $table->string('client_id', 100)->nullable()->comment('客户端 ID');
            $table->boolean('result')->default(true)->comment('交易结果');
            $table->decimal('amount', 30, 6)->default(0)->comment('交易金额');
            $table->string('from', 64)->comment('发送方地址');
            $table->string('to', 64)->comment('接收方地址');
            $table->string('coin_name', 50)->comment('币种名称');
            $table->string('text', 255)->nullable()->comment('备注或描述');
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
