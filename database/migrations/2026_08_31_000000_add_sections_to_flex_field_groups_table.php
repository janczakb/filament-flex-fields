<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flex_field_groups', function (Blueprint $table): void {
            if (! Schema::hasColumn('flex_field_groups', 'sections')) {
                $table->json('sections')->nullable()->after('fields');
            }
        });
    }

    public function down(): void
    {
        Schema::table('flex_field_groups', function (Blueprint $table): void {
            if (Schema::hasColumn('flex_field_groups', 'sections')) {
                $table->dropColumn('sections');
            }
        });
    }
};
