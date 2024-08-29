<?php

// database/seeders/RoleSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
public function run()
{
Role::firstOrCreate(['id' => 1, 'name' => 'admin']); // Ensure ID 1 is for 'admin'
Role::firstOrCreate(['id' => 2, 'name' => 'user']); // Ensure ID 2 is for 'user'
}
}