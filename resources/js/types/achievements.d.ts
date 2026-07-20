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
