<?php

use App\Models\Department;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_department', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('description');
        });

        // Marca o departamento "Geral" existente como padrão, se houver
        Department::withTrashed()
            ->where('name', 'Geral')
            ->whereNull('deleted_at')
            ->oldest()
            ->limit(1)
            ->update(['is_default' => true]);
    }

    public function down(): void
    {
        Schema::table('user_department', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }
};
