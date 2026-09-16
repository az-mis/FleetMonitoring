<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('approving_officers', function (Blueprint $table): void {
            $table->id();
            $table->string('location')->unique();
            $table->string('signee1_name')->nullable();
            $table->string('signee1_title')->nullable();
            $table->string('signee2_name')->nullable();
            $table->string('signee2_title')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approving_officers');
    }
};
