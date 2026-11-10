<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Buat grup 'admin' dan user admin default.
     *
     * Aman dijalankan berulang (idempotent): bila grup atau user sudah ada,
     * tidak dibuat duplikat.
     *
     * Login default: username 'admin', password 'admin123'.
     * WAJIB diganti setelah login pertama.
     */
    public function run(): void
    {
        $groupId = DB::table('groups')->where('name', 'admin')->value('id');

        if ($groupId === null) {
            $groupId = DB::table('groups')->insertGetId([
                'name' => 'admin',
                'description' => 'Administrator',
            ]);
        }

        $userId = DB::table('users')->where('username', 'admin')->value('id');

        if ($userId === null) {
            $userId = DB::table('users')->insertGetId([
                'ip_address' => '127.0.0.1',
                'username' => 'admin',
                'password' => Hash::make('admin123'),
                'email' => 'admin@sekolah.id',
                'created_on' => time(),
                'active' => 1,
                'first_name' => 'Admin',
            ]);
        }

        $exists = DB::table('users_groups')
            ->where('user_id', $userId)
            ->where('group_id', $groupId)
            ->exists();

        if (! $exists) {
            DB::table('users_groups')->insert([
                'user_id' => $userId,
                'group_id' => $groupId,
            ]);
        }
    }
}
