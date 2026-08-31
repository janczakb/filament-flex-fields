<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flex_field_groups', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('target_type')->default('App\\Models\\Model');
            $table->json('fields')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->string('tenant_id')->default('')->index();
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flex_field_groups');
    }
};
