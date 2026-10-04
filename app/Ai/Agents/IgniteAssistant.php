<?php

namespace App\Ai\Agents;

use App\Ai\AssistantTools;
use App\Mcp\Servers\IgniteServer;
use App\Models\Goal;
use App\Models\User;
use App\Rules\GoalEntryRules;
use App\Services\Assistant\AssistantConnection;
use Laravel\Ai\Ai;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Laravel\Ai\Providers\Provider;
use Laravel\Mcp\Server\Tool as McpTool;

#[MaxSteps(8)]
#[UseCheapestModel]
class IgniteAssistant implements Agent, Conversational, HasTools
{
    use Promptable;
    use RemembersConversations;

    public function __construct(
        private readonly User $user,
        private readonly AssistantConnection $connection,
        private readonly ?Goal $currentGoal = null,
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): string
    {
        $language = config('locales.supported')[$this->user->locale] ?? 'English';
        $timezone = $this->user->timezone ?? config('app.timezone');

        $context = [
            'You are the assistant built into the Ignite app, talking with the signed-in user in a side panel.',
            "Reply in {$language}.",
            "The user's timezone is {$timezone}, and today is ".GoalEntryRules::todayForTimezone($timezone).' there.',
            'Deleting a goal, entry, or category pauses for the user to approve it in the app. Call the delete tool directly and never ask for a confirmation token.',
        ];

        if ($this->currentGoal !== null) {
            $context[] = "The user is currently looking at the goal with id {$this->currentGoal->id}. When they say \"this goal\" or give no goal, they mean that one.";
        }

        return IgniteServer::INSTRUCTIONS."\n\n".implode("\n", $context);
    }

    /**
     * Get the tools available to the agent.
     *
     * @return list<Tool|McpTool>
     */
    public function tools(): iterable
    {
        return app(AssistantTools::class)->all();
    }

    public function provider(): Provider
    {
        return Ai::build($this->connection->providerConfiguration);
    }

    public function model(): ?string
    {
        return $this->connection->model;
    }
}
