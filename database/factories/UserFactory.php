<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
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
            // Wajib untuk app ini: akun aktif & tidak expired (dicek middleware EnsureIsActive)
            'is_active' => true,
            'is_permanent' => true,
            'paket' => 'standar',
            'role_id' => fn () => \App\Models\Role::firstOrCreate(
                ['name' => 'User'],
                ['description' => 'User']
            )->id,
            'cabang_id' => fn () => \App\Models\Cabang::firstOrCreate(
                ['nama' => 'Cabang Utama'],
                ['alamat' => 'Jl. Raya Utama No. 88', 'telepon' => '0812-3456-7890', 'aktif' => true]
            )->id,
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
}
