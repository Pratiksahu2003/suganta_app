<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class AiSubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // s_type = 2 reserved for AI adviser plans
        $plans = [
            [
                'name' => 'AI Basic',
                'slug' => 'ai-basic',
                'description' => 'Basic AI adviser plan',
                'price' => 999, // in paise/minor units if you use that, or 499.00
                'currency' => 'INR',
                'billing_period' => 'monthly',
                'max_images' => 0,
                'max_files' => 0,
                'features' => [
                    'ai_tokens' => 200000,
                ],
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 1,
                's_type' => 2,
            ],
            [
                'name' => 'AI Pro',
                'slug' => 'ai-pro',
                'description' => 'Pro AI adviser plan',
                'price' => 1799,
                'currency' => 'INR',
                'billing_period' => 'monthly',
                'max_images' => 0,
                'max_files' => 0,
                'features' => [
                    'ai_tokens' => 500000,
                ],
                'is_popular' => true,
                'is_active' => true,
                'sort_order' => 2,
                's_type' => 3,
            ],
        ];

        foreach ($plans as $data) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $data['slug']],
                $data,
            );
        }
    }
}

