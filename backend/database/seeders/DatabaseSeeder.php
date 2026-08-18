<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Safe to run multiple times (all seeders are idempotent).
     */
    public function run(): void
    {
        // Roles (admin guard + customer guard) — idempotent.
        $this->call([
            RoleSeeder::class,
        ]);

        // Default admin user (admin panel login: admin@gmail.com / 123456).
        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name'     => 'Shop Genie Admin',
                'password' => bcrypt('123456'),
                'status'   => 1,
            ]
        );

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'admin'],
            ['name' => 'admin', 'guard_name' => 'admin']
        );

        if (! $admin->hasRole($adminRole)) {
            $admin->assignRole($adminRole);
        }

        // Storefront demo data (settings, contacts, shipping, categories,
        // products, banner, coupon) — idempotent.
        $this->call([
            DemoDataSeeder::class,
        ]);
    }
}
