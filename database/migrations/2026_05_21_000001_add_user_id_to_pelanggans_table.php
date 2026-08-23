<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pelanggans', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
        });

        // Sinkron data lama: user dengan role User (id=3) yang belum punya pelanggan
        $users = DB::table('users')->where('role_id', 3)->get();
        foreach ($users as $user) {
            // Cek apakah sudah ada pelanggan dengan no_hp yang sama
            $exists = DB::table('pelanggans')->where('no_hp', $user->phone)->first();
            if ($exists) {
                // Link pelanggan yang sudah ada ke user ini
                DB::table('pelanggans')->where('id', $exists->id)->update(['user_id' => $user->id]);
            } else {
                // Buat pelanggan baru dari data user
                DB::table('pelanggans')->insert([
                    'user_id' => $user->id,
                    'nama' => $user->name,
                    'no_hp' => $user->phone ?? '-',
                    'alamat' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Sinkron data lama: pelanggan yang belum punya user → buat akun user
        $pelanggans = DB::table('pelanggans')->whereNull('user_id')->get();
        foreach ($pelanggans as $pel) {
            // Cek apakah sudah ada user dengan phone/no_hp yang sama
            $existingUser = DB::table('users')->where('phone', $pel->no_hp)->first();
            if ($existingUser) {
                DB::table('pelanggans')->where('id', $pel->id)->update(['user_id' => $existingUser->id]);
            } else {
                // Buat user baru dari data pelanggan
                $email = $pel->no_hp . '@fixpro.local';
                // Pastikan email unik
                $counter = 1;
                while (DB::table('users')->where('email', $email)->exists()) {
                    $email = $pel->no_hp . "_{$counter}@fixpro.local";
                    $counter++;
                }
                $userId = DB::table('users')->insertGetId([
                    'name' => $pel->nama,
                    'email' => $email,
                    'password' => bcrypt($pel->no_hp), // default password = no_hp
                    'phone' => $pel->no_hp,
                    'role_id' => 3,
                    'cabang_id' => 1,
                    'is_active' => true,
                    'is_permanent' => false,
                    'login_expires_at' => now()->addMonth(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('pelanggans')->where('id', $pel->id)->update(['user_id' => $userId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('pelanggans', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
