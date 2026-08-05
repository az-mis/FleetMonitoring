<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->date('birth_date')->nullable()->after('avatar');
            $table->string('address')->nullable()->after('birth_date');
            $table->string('license_front')->nullable()->after('address');
            $table->string('license_back')->nullable()->after('license_front');
            $table->date('license_expiration_date')->nullable()->after('license_back');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['birth_date', 'address', 'license_front', 'license_back', 'license_expiration_date']);
        });
    }
};
