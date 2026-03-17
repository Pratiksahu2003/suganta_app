<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;

class AiSubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [

            [
                'name' => 'AI Adviser Basic',
                'slug' => 'ai-adviser-basic',
                'description' =>
                    'Starter AI Adviser plan designed for individuals who need smart AI guidance for education, career decisions, and general problem solving.',

                'price' => 999,
                'currency' => 'INR',
                'billing_period' => 'monthly',

                'max_images' => 10,
                'max_files' => 5,

                'features' => [

                    /* AI Adviser Usage */
                    'ai_advice_tokens' => 200000,
                    'advice_requests_per_month' => 300,

                    /* Adviser Capabilities */
                    'career_guidance' => true,
                    'education_consultation' => true,
                    'basic_document_review' => true,
                    'study_plan_generation' => true,

                    /* Performance */
                    'response_speed' => 'standard',
                    'priority_processing' => false,

                    /* Personalization */
                    'personalized_recommendations' => true,
                    'custom_queries' => true,

                    /* Support */
                    'email_support' => true,
                    'priority_support' => false,

                    /* Storage */
                    'data_storage_mb' => 100,
                ],

                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 1,
                's_type' => 3,
            ],

            [
                'name' => 'AI Adviser Pro',
                'slug' => 'ai-adviser-pro',
                'description' =>
                    'Advanced AI Adviser plan for students, professionals, and entrepreneurs who require deeper insights, document analysis, and strategic AI guidance.',

                'price' => 1799,
                'currency' => 'INR',
                'billing_period' => 'monthly',

                'max_images' => 50,
                'max_files' => 25,

                'features' => [

                    /* AI Adviser Usage */
                    'ai_advice_tokens' => 500000,
                    'advice_requests_per_month' => 1000,

                    /* Adviser Capabilities */
                    'career_guidance' => true,
                    'education_consultation' => true,
                    'business_advice' => true,
                    'document_analysis' => true,
                    'resume_review' => true,
                    'study_plan_generation' => true,

                    /* Performance */
                    'response_speed' => 'fast',
                    'priority_processing' => true,

                    /* Personalization */
                    'personalized_recommendations' => true,
                    'custom_queries' => true,
                    'goal_based_guidance' => true,

                    /* Support */
                    'email_support' => true,
                    'priority_support' => false,

                    /* Storage */
                    'data_storage_mb' => 500,
                ],

                'is_popular' => true,
                'is_active' => true,
                'sort_order' => 2,
                's_type' => 3,
            ],

            [
                'name' => 'AI Adviser Advanced',
                'slug' => 'ai-adviser-advanced',
                'description' =>
                    'Premium AI Adviser plan built for businesses, consultants, and power users who need strategic AI insights, advanced document analysis, and unlimited advisory assistance.',

                'price' => 2999,
                'currency' => 'INR',
                'billing_period' => 'monthly',

                'max_images' => 200,
                'max_files' => 100,

                'features' => [

                    /* AI Adviser Usage */
                    'ai_advice_tokens' => 1000000,
                    'advice_requests_per_month' => 5000,

                    /* Adviser Capabilities */
                    'career_guidance' => true,
                    'education_consultation' => true,
                    'business_strategy_advice' => true,
                    'advanced_document_analysis' => true,
                    'resume_review' => true,
                    'study_plan_generation' => true,
                    'market_insight_reports' => true,
                

                    /* Performance */
                    'response_speed' => 'priority',
                    'priority_processing' => true,

                    /* Personalization */
                    'personalized_recommendations' => true,
                    'custom_queries' => true,
                    'goal_based_guidance' => true,
                    'strategic_planning_assistance' => true,

                    /* Support */
                    'email_support' => true,
                    'priority_support' => true,

                    /* Storage */
                    'data_storage_mb' => 2000,
                ],

                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 3,
                's_type' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}