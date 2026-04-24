<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bug_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_bug_id')->constrained('bugs')->cascadeOnDelete();
            $table->foreignId('target_bug_id')->constrained('bugs')->cascadeOnDelete();
            $table->string('relation_type')->default('related');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bug_links');
    }
};
