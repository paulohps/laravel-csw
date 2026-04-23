<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('composer_security_vulnerabilities', function (Blueprint $table): void {
            $table->id();
            $table->string('package_name')->index();
            $table->string('version');
            $table->string('advisory_id')->unique();
            $table->text('title');
            $table->string('link')->nullable();
            $table->string('cve')->nullable();
            $table->timestamp('found_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('composer_security_vulnerabilities');
    }
};
