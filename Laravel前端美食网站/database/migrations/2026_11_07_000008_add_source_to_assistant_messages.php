<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 记录每条回答来自 DeepSeek 还是本地知识库，方便排查与统计
     */
    public function up(): void
    {
        Schema::table('assistant_messages', function (Blueprint $table) {
            $table->string('source', 16)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('assistant_messages', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
