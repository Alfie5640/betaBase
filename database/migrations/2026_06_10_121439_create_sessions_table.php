<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('climbing_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('place');
            $table->date('date');
            $table->time('time_start');
            $table->time('time_end')->nullable();
            $table->boolean('needs_tr_belayer')->default(false);
            $table->boolean('needs_lead_belayer')->default(false);
            $table->string('other_need')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('climbing_sessions');
    }
};