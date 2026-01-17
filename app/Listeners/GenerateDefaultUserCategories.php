<?php

namespace App\Listeners;

use App\Models\Category;
use Illuminate\Auth\Events\Registered;

class GenerateDefaultUserCategories
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        $user = $event->user;

        $categories = [
            [
                'name' => 'Health & Fitness',
                'color' => '#ef4444',
                'icon' => 'heart-pulse',
                'description' => 'Exercise, nutrition, sleep, weight goals',
            ],
            [
                'name' => 'Career & Work',
                'color' => '#3b82f6',
                'icon' => 'briefcase',
                'description' => 'Promotions, skills, projects, networking',
            ],
            [
                'name' => 'Finance & Money',
                'color' => '#10b981',
                'icon' => 'wallet',
                'description' => 'Savings, debt reduction, investments',
            ],
            [
                'name' => 'Learning & Education',
                'color' => '#8b5cf6',
                'icon' => 'graduation-cap',
                'description' => 'Courses, certifications, reading, languages',
            ],
            [
                'name' => 'Personal Development',
                'color' => '#f59e0b',
                'icon' => 'sparkles',
                'description' => 'Habits, mindfulness, journaling, self-improvement',
            ],
            [
                'name' => 'Relationships & Social',
                'color' => '#ec4899',
                'icon' => 'users',
                'description' => 'Family time, friendships, communication',
            ],
            [
                'name' => 'Hobbies & Creativity',
                'color' => '#f97316',
                'icon' => 'palette',
                'description' => 'Art, music, crafts, side projects',
            ],
            [
                'name' => 'Home & Lifestyle',
                'color' => '#64748b',
                'icon' => 'home',
                'description' => 'Organization, cleaning, home improvement',
            ],
            [
                'name' => 'Travel & Adventure',
                'color' => '#06b6d4',
                'icon' => 'plane',
                'description' => 'Trips, experiences, exploration',
            ],
            [
                'name' => 'Wellness & Mental Health',
                'color' => '#14b8a6',
                'icon' => 'brain',
                'description' => 'Meditation, therapy, stress management, self-care',
            ],
        ];

        foreach ($categories as $category) {
            $user->categories()->save(new Category($category));
        }
    }
}
