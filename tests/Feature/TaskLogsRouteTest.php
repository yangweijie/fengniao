<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskLogsRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_logs_route_exists(): void
    {
        // 创建用户和任务
        $user = User::factory()->create();
        $task = Task::factory()->create();

        // 测试路由是否存在
        $this->actingAs($user);

        $response = $this->get(route('filament.admin.resources.tasks.logs', $task));

        // 应该返回200状态码（如果用户有权限）或者302重定向
        $this->assertContains($response->status(), [200, 302, 403]);
    }

    public function test_task_logs_route_name_is_correct(): void
    {
        $task = Task::factory()->create();

        // 测试路由名称是否正确
        $url = route('filament.admin.resources.tasks.logs', $task);

        $this->assertStringContainsString("/admin/tasks/{$task->id}/logs", $url);
    }

    public function test_task_logs_route_requires_authentication(): void
    {
        $task = Task::factory()->create();

        // 未认证用户应该被重定向到登录页面
        $response = $this->get(route('filament.admin.resources.tasks.logs', $task));

        $this->assertEquals(302, $response->status());
        $this->assertStringContainsString('login', $response->headers->get('Location'));
    }
}
