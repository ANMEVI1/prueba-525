<?php

namespace Database\Factories;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => fake()->randomElement(AuditAction::cases()),
            'subject_type' => null,
            'subject_id' => null,
            'description' => fake()->sentence(),
            'properties' => ['ip' => fake()->ipv4()],
            'ip_address' => fake()->ipv4(),
            'created_at' => now(),
        ];
    }
}
