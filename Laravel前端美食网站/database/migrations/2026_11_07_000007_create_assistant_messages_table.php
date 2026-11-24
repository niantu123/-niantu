<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 美食助手聊天记录：登录用户按 user_id 归档，游客按 session_key 归档
     */
    public function up(): void
    {
        Schema::create('assistant_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_key', 64)->index();
            $table->string('role', 12); // user 提问 / assistant 回答
            $table->text('content');
            $table->json('cards')->nullable();   // 回答里附带的菜谱/餐厅/食材卡片
            $table->json('quick')->nullable();   // 快捷追问建议
            $table->string('intent', 32)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_messages');
    }
};
