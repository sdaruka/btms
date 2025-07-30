<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CustomerGroup; // Import the model

class CustomerGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CustomerGroup::firstOrCreate(
            ['name' => 'Regular'],
            ['description' => 'Standard customers']
        );

        CustomerGroup::firstOrCreate(
            ['name' => 'VIP'],
            ['description' => 'Very Important Customers with special benefits']
        );

        CustomerGroup::firstOrCreate(
            ['name' => 'Wholesale'],
            ['description' => 'Customers who purchase in bulk']
        );

        CustomerGroup::firstOrCreate(
            ['name' => 'Blocked'],
            ['description' => 'Customers who are temporarily or permanently blocked from certain services']
        );

        // Add more groups as needed
    }
}