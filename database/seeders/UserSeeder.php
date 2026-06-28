<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Duas empresas (tenants) para demonstrar o isolamento de dados.
        $companies = [
            ['name' => 'Example Inc', 'slug' => 'example', 'domain' => 'example.com'],
            ['name' => 'Acme Ltda',   'slug' => 'acme',    'domain' => 'acme.com'],
        ];

        foreach ($companies as $data) {
            $company = Company::firstOrCreate(
                ['slug' => $data['slug']],
                ['name' => $data['name']]
            );

            $admin = User::firstOrCreate(
                ['email' => "admin@{$data['domain']}"],
                [
                    'company_id' => $company->id,
                    'name'       => "Admin {$data['name']}",
                    'password'   => bcrypt('password'),
                ]
            );
            $admin->assignRole('admin');

            $user = User::firstOrCreate(
                ['email' => "user@{$data['domain']}"],
                [
                    'company_id' => $company->id,
                    'name'       => "User {$data['name']}",
                    'password'   => bcrypt('password'),
                ]
            );
            $user->assignRole('user');
        }
    }
}
