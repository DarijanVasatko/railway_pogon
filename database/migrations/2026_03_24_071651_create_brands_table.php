<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create the brands table
        Schema::create('brands', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('name');
            $blueprint->timestamps();
        });

        // 2. Add brand_id to the existing 'proizvod' table
        Schema::table('proizvod', function (Blueprint $table) {
            // Using unsignedBigInteger to match the 'id' type in Laravel
            $table->unsignedBigInteger('brand_id')->nullable()->after('Tip_ID');
            
            // Set up the foreign key relationship
            $table->foreign('brand_id')
                  ->references('id')
                  ->on('brands')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('proizvod', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropColumn('brand_id');
        });
        Schema::dropIfExists('brands');
    }
};
