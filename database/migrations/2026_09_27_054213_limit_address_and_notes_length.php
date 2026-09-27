<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Truncar cualquier dato existente que exceda el nuevo límite,
        // para que el cambio de tipo no falle ni pierda filas.
        DB::statement("UPDATE people SET address = LEFT(address, 200) WHERE CHAR_LENGTH(address) > 200");
        DB::statement("UPDATE patients SET administrative_notes = LEFT(administrative_notes, 500) WHERE CHAR_LENGTH(administrative_notes) > 500");

        Schema::table('people', function (Blueprint $table) {
            $table->string('address', 200)->nullable()->change();
        });
        Schema::table('patients', function (Blueprint $table) {
            $table->string('administrative_notes', 500)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->text('address')->nullable()->change();
        });
        Schema::table('patients', function (Blueprint $table) {
            $table->text('administrative_notes')->nullable()->change();
        });
    }
};