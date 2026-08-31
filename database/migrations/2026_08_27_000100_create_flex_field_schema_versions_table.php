<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flex_field_schema_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('flex_field_group_id')
                ->nullable()
                ->constrained('flex_field_groups')
                ->nullOnDelete();
            $table->string('schema_id')->index();
            $table->unsignedInteger('version');
            $table->json('schema');
            $table->string('checksum', 64);
            $table->string('actor')->nullable();
            $table->string('state', 16)->default('draft')->index();
            $table->timestamp('published_at');
            $table->timestamps();

            $table->unique(['schema_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flex_field_schema_versions');
    }
};
