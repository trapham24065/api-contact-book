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
        Schema::create('contact_attributes', static function (Blueprint $table) {
            $table->id('attribute_id');
            $table->foreignId('contact_id')->constrained('contacts', 'contact_id')->onDelete('cascade');
            $table->string('attr_key', 100);
            $table->text('attr_value');
            $table->timestamps();
            
            //Make sure attr_key is unique for each contact
            $table->unique(['contact_id', 'attr_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_attributes');
    }

};
