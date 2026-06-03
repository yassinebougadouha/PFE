<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role_python')) {
                $table->string('role_python', 20)->nullable()->after('role');
            }

            if (!Schema::hasColumn('users', 'hashed_password')) {
                $table->string('hashed_password')->nullable()->after('password');
            }
        });

        if (Schema::hasColumn('users', 'role_python')) {
            DB::table('users')
                ->whereNull('role_python')
                ->update([
                    'role_python' => DB::raw("
                        CASE
                            WHEN role = 'super_admin' THEN 'ADMIN'
                            WHEN role = 'admin' THEN 'AGENT'
                            ELSE 'CLIENT'
                        END
                    "),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role_python')) {
                $table->dropColumn('role_python');
            }

            if (Schema::hasColumn('users', 'hashed_password')) {
                $table->dropColumn('hashed_password');
            }
        });
    }
};
