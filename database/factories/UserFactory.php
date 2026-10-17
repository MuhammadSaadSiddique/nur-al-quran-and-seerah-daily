<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create($attributes = [], ?Model $parent = null)
    {
        $attributes = is_array($attributes) ? $attributes : [];
        $forcedKeys = [
            'is_admin', 'is_researcher', 'quran_access_token', 'quran_refresh_token',
            'expert_category_id', 'total_score', 'total_questions', 'seerah_read_count',
            'quran_history_read_count', 'seerah_quiz_count', 'completed_paras', 'para_mastery',
            'difficulty_stats', 'bookmarked_questions', 'seerah_quiz_best_score',
            'streak_count', 'longest_streak', 'last_streak_date', 'streak_history',
        ];
        $forced = Arr::only($attributes, $forcedKeys);
        $attributes = Arr::except($attributes, array_keys($forced));

        $result = parent::create($attributes, $parent);

        $apply = function (User $user) use ($forced) {
            if ($forced !== []) {
                $user->forceFill($forced)->save();
            }
        };

        if ($result instanceof User) {
            $apply($result);
        } elseif ($result instanceof \Illuminate\Support\Collection) {
            $result->each(fn ($user) => $user instanceof User ? $apply($user) : null);
        }

        return $result;
    }
}
