<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('media_instances', function (Blueprint $table) {
            $table->ulid('id');
            $table->foreignId('mediaitem_id')->constrained('media_items', 'id');
            $table->morphs('mediaowner');
            $table->string('property');
            $table->json('custom_properties')->nullable();
            $table->foreignId('created_by')->constrained('users', 'id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('media_instances');
    }
};
