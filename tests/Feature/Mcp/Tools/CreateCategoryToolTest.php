<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\CreateCategoryTool;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\AssertsUserData;
use Tests\TestCase;

class CreateCategoryToolTest extends TestCase
{
    use AssertsUserData;
    use RefreshDatabase;

    public function test_a_write_scoped_token_creates_a_category_owned_by_the_actor(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(CreateCategoryTool::class, ['name' => 'Woodworking'])
            ->assertOk()
            ->assertSee('Created the category Woodworking.');

        $this->assertUserDataHas(Category::class, [
            'user_id' => $user->id,
            'name' => 'Woodworking',
        ]);
    }

    public function test_a_read_only_token_cannot_use_the_tool(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(CreateCategoryTool::class, ['name' => 'Woodworking'])
            ->assertHasErrors();

        $this->assertUserDataMissing(Category::class, ['name' => 'Woodworking']);
    }

    public function test_the_name_is_required(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(CreateCategoryTool::class, [])
            ->assertHasErrors();
    }

    public function test_a_whitespace_only_name_is_rejected(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(CreateCategoryTool::class, ['name' => '   '])
            ->assertHasErrors();

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_a_name_longer_than_the_column_is_rejected(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(CreateCategoryTool::class, ['name' => str_repeat('a', 101)])
            ->assertHasErrors();

        $this->assertDatabaseCount('categories', 0);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidColourProvider(): array
    {
        return [
            'a colour name' => ['red'],
            'three digit shorthand' => ['#fff'],
            'non hex characters' => ['#gggggg'],
            'missing the leading hash' => ['6366f1'],
            'too few digits' => ['#6366f'],
            'too many digits' => ['#6366f12'],
            'a css function' => ['rgb(99,102,241)'],
        ];
    }

    #[DataProvider('invalidColourProvider')]
    public function test_a_colour_that_is_not_a_six_digit_hex_is_rejected(string $color): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(CreateCategoryTool::class, [
            'name' => 'Woodworking',
            'color' => $color,
        ])->assertHasErrors();

        $this->assertDatabaseCount('categories', 0);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validColourProvider(): array
    {
        return [
            'lowercase' => ['#6366f1'],
            'uppercase' => ['#6366F1'],
            'mixed case' => ['#Ab12Cd'],
            'all digits' => ['#123456'],
        ];
    }

    #[DataProvider('validColourProvider')]
    public function test_a_six_digit_hex_colour_is_accepted_and_stored_in_lowercase(string $color): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(CreateCategoryTool::class, [
            'name' => 'Woodworking',
            'color' => $color,
        ])->assertOk();

        $this->assertUserDataHas(Category::class, [
            'name' => 'Woodworking',
            'color' => strtolower($color),
        ]);
    }

    public function test_an_omitted_colour_falls_back_to_the_column_default(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(CreateCategoryTool::class, ['name' => 'Woodworking'])
            ->assertOk();

        $this->assertUserDataHas(Category::class, [
            'name' => 'Woodworking',
            'color' => '#6366f1',
        ]);
    }

    public function test_a_supplied_owner_is_ignored(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(CreateCategoryTool::class, [
            'name' => 'Woodworking',
            'user_id' => $otherUser->id,
        ])->assertOk();

        $this->assertUserDataHas(Category::class, ['name' => 'Woodworking', 'user_id' => $user->id]);
        $this->assertDatabaseMissing('categories', ['user_id' => $otherUser->id]);
    }
}
