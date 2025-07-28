<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['browser', 'api']),
            'status' => $this->faker->randomElement(['enabled', 'disabled']),
            'cron_expression' => '0 9 * * *', // 每天9点
            'script_content' => $this->faker->text(500),
            'workflow_data' => null,
            'domain' => $this->faker->domainName(),
            'is_exclusive' => $this->faker->boolean(20), // 20%概率为独占
            'login_config' => [
                'username_env' => 'TEST_USERNAME',
                'password_env' => 'TEST_PASSWORD',
                'login_url' => $this->faker->url(),
                'login_check_url' => $this->faker->url(),
                'logged_in_selector' => '.user-menu'
            ],
            'env_vars' => [
                'TEST_USERNAME' => $this->faker->userName(),
                'TEST_PASSWORD' => $this->faker->password()
            ],
            'notification_config' => [
                'channels' => ['email'],
                'on_success' => true,
                'on_failure' => true
            ]
        ];
    }
}
