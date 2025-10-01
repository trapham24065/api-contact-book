<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id('request_id');
            $table->foreignId('user_id')->nullable()->constrained('users', 'user_id')->onDelete('set null');
            $table->string('api_key', 255)->nullable();
            $table->string('method', 10);
            $table->string('endpoint', 255);
            $table->integer('status_code');
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();

            $table->timestamp('requested_at');
            $table->date('req_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }

};
