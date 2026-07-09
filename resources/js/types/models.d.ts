export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    timezone: string;
    total_points: number;
    level: number;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;

    categories?: Category[];
    goals?: Goal[];
    achievements?: Achievement[];
    unlockedAchievements?: Achievement[];
    achievementsInProgress?: Achievement[];
    activeGoals?: Goal[];
    completedGoals?: Goal[];
}

export interface Category {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    color: string;
    icon: string | null;
    user_id: number;
    order: number;
    created_at: string;
    updated_at: string;

    user?: User;
    goals?: Goal[];

    goals_count?: number;
    active_goals_count?: number;
    completed_goals_count?: number;
}

export interface Goal {
    id: number;
    user_id: number;
    category_id: number | null;
    title: string;
    description: string | null;
    icon: string | null;
    type: 'simple' | 'quantifiable' | 'recurring' | 'multi_step';
    direction: 'ascending' | 'descending';
    target_value: number | null;
    initial_value: number;
    current_value: number;
    unit: string | null;
    recurrence: 'daily' | 'weekly' | 'monthly' | 'annually' | null;
    start_date: string | null;
    deadline: string | null;
    completed_at: string | null;
    status:
        | 'not_started'
        | 'in_progress'
        | 'completed'
        | 'paused'
        | 'abandoned';
    priority: 'low' | 'medium' | 'high';
    polarity: 'positive' | 'negative';
    points: number;
    is_public: boolean;
    order: number;
    created_at: string;
    updated_at: string;

    // Attributes
    progress_percentage: number;
    is_overdue: boolean;
    is_completed: boolean;
    streak?: StreakData;

    // Relationships (if loaded)
    user?: User;
    category?: Category;
    entries?: GoalEntry[];
    milestones?: Milestone[];
}

export interface GoalEntry {
    id: number;
    goal_id: number;
    value: number;
    previous_value: number;
    increment_value: number;
    note: string | null;
    entry_date: string;
    attachment_path: string | null;
    attachment_type: string | null;
    created_at: string;
    updated_at: string;

    goal?: Goal;
}

export interface Milestone {
    id: number;
    goal_id: number;
    title: string;
    description: string | null;
    target_value: number | null;
    order: number;
    is_completed: boolean;
    is_reached: boolean;
    completed_at: string | null;
    points_reward: number | null;
    created_at: string;
    updated_at: string;

    goal?: Goal;
}

export interface Achievement {
    id: number;
    name: string;
    slug: string;
    description: string;
    icon: string;
    badge_image: string | null;
    type: 'goal_completion' | 'streak' | 'points' | 'consistency' | 'special';
    criteria: AchievementCriteria;
    points_reward: number;
    rarity: 'common' | 'rare' | 'epic' | 'legendary';
    order: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;

    creator?: User;
    users?: User[];
}

export interface UserAchievement {
    id: number;
    user_id: number;
    achievement_id: number;
    progress: number;
    unlocked_at: string | null;
    is_seen: boolean;
    created_at: string;
    updated_at: string;

    user?: User;
    achievement?: Achievement;
}

export interface StreakData {
    current: number;
    longest: number;
    unit: string;
    current_period_satisfied: boolean;
}

// Define the structure of criteria based on achievement type
export type AchievementCriteria =
    | GoalCompletionCriteria
    | StreakCriteria
    | PointsCriteria
    | ConsistencyCriteria
    | SpecialCriteria;

export interface GoalCompletionCriteria {
    goals_completed: number;
    category_id?: number; // Optional: specific category
}

export interface StreakCriteria {
    streak_days: number;
}

export interface PointsCriteria {
    points_earned: number;
}

export interface ConsistencyCriteria {
    consecutive_days?: number;
    early_entries?: number;
    weekend_completions?: number;
}

export interface SpecialCriteria {
    [key: string]: any;
}
