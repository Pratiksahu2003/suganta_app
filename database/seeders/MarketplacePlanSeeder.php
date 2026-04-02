<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class MarketplacePlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $plans = [
            [
                'name' => 'Marketplace Basic',
                'slug' => 'marketplace-basic',
                'description' => 'Perfect for getting started and selling a few items.',
                'price' => 10.00,
                'currency' => 'INR',
                'billing_period' => 'month',
                'max_listings' => 1,
                'features' => [
                    'List up to 5 items',
                    'Basic seller dashboard',
                    '10% platform commission',
                    'Community support'
                ],
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 1,
                's_type' => 6, // 6 = Marketplace Subscription
            ],
            [
                'name' => 'Marketplace Pro',
                'slug' => 'marketplace-pro',
                'description' => 'Great for regular sellers wanting more visibility.',
                'price' => 99,
                'currency' => 'INR',
                'billing_period' => 'month',
                'max_listings' => 10,
                'features' => [
                    'List up to 50 items',
                    'Advanced analytics',
                    'Priority support',
                    'Featured listings'
                ],
                'is_popular' => true,
                'is_active' => true,
                'sort_order' => 2,
                's_type' => 6,
            ],
            [
                'name' => 'Marketplace Unlimited',
                'slug' => 'marketplace-unlimited',
                'description' => 'For power sellers and large digital catalogs.',
                'price' => 199.00,
                'currency' => 'INR',
                'billing_period' => 'month',
                'max_listings' => 25,
                'features' => [
                    'Unlimited listings',
                    'Premium seller badge',
                    'Dedicated account manager',
                    'Lowest transaction fees'
                ],
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 3,
                's_type' => 6,
            ]
        ];

        foreach ($plans as $planData) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }

        $this->command->info('Marketplace subscription plans seeded successfully.');
    }
}
