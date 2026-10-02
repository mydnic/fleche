<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who already imported which pack, so each importer counts once. The importer
 * is an HMAC of "user:<id>" or "ip:<address>", never the raw value.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hub_imports', function (Blueprint $table) {
            $table->foreignId('hub_pack_id')->constrained()->cascadeOnDelete();
            $table->char('importer', 64);
            $table->timestamp('created_at')->nullable();

            $table->primary(['hub_pack_id', 'importer']);
        });
    }
};
