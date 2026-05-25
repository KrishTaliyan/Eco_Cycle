<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\RecyclingCenter;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $permissions = collect([
            ['name' => 'workspace.view', 'label' => 'View workspace'],
            ['name' => 'workspace.manage', 'label' => 'Manage workspace'],
            ['name' => 'admin.view', 'label' => 'View admin dashboard'],
            ['name' => 'certificates.issue', 'label' => 'Issue certificates'],
            ['name' => 'pickups.manage', 'label' => 'Manage pickups'],
        ])->map(fn (array $permission) => Permission::updateOrCreate(
            ['name' => $permission['name']],
            ['label' => $permission['label']],
        ));

        $customer = Role::updateOrCreate(
            ['name' => 'customer'],
            ['label' => 'Customer', 'description' => 'Can submit devices, track rewards, and find centers'],
        );
        $shopOwner = Role::updateOrCreate(
            ['name' => 'shop_owner'],
            ['label' => 'Shop Owner', 'description' => 'Can manage recycling centers and approve customer submissions'],
        );
        $admin = Role::updateOrCreate(
            ['name' => 'admin'],
            ['label' => 'Admin', 'description' => 'Full company operations visibility'],
        );

        $customer->permissions()->sync($permissions->whereIn('name', ['workspace.view'])->pluck('id'));
        $shopOwner->permissions()->sync($permissions->whereIn('name', ['workspace.view', 'workspace.manage', 'certificates.issue', 'pickups.manage'])->pluck('id'));
        $admin->permissions()->sync($permissions->pluck('id'));

        $user = User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'role' => 'admin',
                'email_verified_at' => now(),
            ],
        );

        $user->assignRole('admin');
        $user->settings()->firstOrCreate([]);

        $shop = User::query()->updateOrCreate(
            ['email' => 'shop@example.com'],
            [
                'name' => 'Green Loop Owner',
                'password' => 'password',
                'role' => 'shop_owner',
                'organization' => 'Green Loop Recycling',
                'email_verified_at' => now(),
            ],
        );
        $shop->assignRole('shop_owner');
        $shop->settings()->firstOrCreate([]);

        $customerUser = User::query()->updateOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name' => 'Eco Customer',
                'password' => 'password',
                'role' => 'customer',
                'email_verified_at' => now(),
            ],
        );
        $customerUser->assignRole('customer');
        $customerUser->settings()->firstOrCreate([]);

        RecyclingCenter::query()->updateOrCreate(
            ['name' => 'Green Loop Center', 'city' => 'Delhi NCR'],
            [
                'shop_owner_id' => $shop->id,
                'state' => 'Delhi',
                'pincode' => '110001',
                'address' => 'Connaught Place collection lane, New Delhi',
                'phone' => '+91 98765 43210',
                'email' => 'center@greenloop.test',
                'accepted_categories' => ['Mobile', 'Laptop', 'Battery', 'TV'],
                'status' => 'active',
                'opening_hours' => '10:00 AM - 7:00 PM',
            ],
        );
    }
}
