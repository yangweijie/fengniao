# Requirements Document

## Introduction

本项目旨在构建一个基于 dcat-plus-admin 和 Laravel Dusk 的自动化任务管理平台，类似青龙面板的功能，但专注于浏览器自动化任务。系统将提供可视化的任务管理界面，支持定时执行浏览器自动化脚本，并具备完整的日志记录、通知推送和权限管理功能。系统使用 SQLite 数据库保持轻便性，适合中小型团队的自动化需求。

## Requirements

### Requirement 1

**User Story:** 作为系统管理员，我想要创建和管理自动化任务，以便能够定时执行各种浏览器自动化脚本和API请求脚本

#### Acceptance Criteria

1. WHEN 管理员访问任务管理页面 THEN 系统 SHALL 显示所有已创建的任务列表，包含任务名称、状态（启用/禁用）、下次运行时间、最后执行结果
2. WHEN 管理员点击创建任务按钮 THEN 系统 SHALL 提供任务创建表单，包含任务名称、描述、任务类型（浏览器任务/API任务）、Cron表达式、脚本内容等字段
3. WHEN 管理员选择任务类型 THEN 系统 SHALL 根据类型显示相应的脚本编辑器和配置选项
4. WHEN 管理员提交有效的任务信息 THEN 系统 SHALL 保存任务到数据库并返回成功消息
5. WHEN 管理员编辑现有任务 THEN 系统 SHALL 允许修改任务配置并保存更改
6. WHEN 管理员删除任务 THEN 系统 SHALL 确认删除操作并从数据库中移除任务记录
7. WHEN 管理员切换任务状态 THEN 系统 SHALL 允许启用或禁用任务，禁用的任务不会被调度执行
8. WHEN 任务列表显示 THEN 系统 SHALL 计算并显示每个启用任务的下次运行时间
9. WHEN 管理员点击日志按钮 THEN 系统 SHALL 提供查看历史日志和实时日志的选项

### Requirement 2

**User Story:** 作为系统用户，我想要执行基于 Laravel Dusk 的浏览器自动化脚本和API请求脚本，以便自动完成各种网页操作和接口调用任务

#### Acceptance Criteria

1. WHEN 浏览器任务到达预定执行时间 THEN 系统 SHALL 自动启动 Laravel Dusk 浏览器实例执行脚本
2. WHEN API任务到达预定执行时间 THEN 系统 SHALL 在后台执行HTTP请求脚本
3. WHEN 用户手动触发任务执行 THEN 系统 SHALL 立即启动相应类型的执行器运行指定脚本
4. WHEN 浏览器脚本执行过程中 THEN 系统 SHALL 实时记录执行日志、浏览器操作和自动截图
5. WHEN 脚本执行完成 THEN 系统 SHALL 记录执行结果（成功/失败）、执行时长和相关截图
6. WHEN 脚本执行失败 THEN 系统 SHALL 记录错误信息、堆栈跟踪和失败时的截图
7. WHEN 浏览器任务执行 THEN 系统 SHALL 在关键步骤自动截图并保存到日志中
8. WHEN 开发者需要调试 THEN 系统 SHALL 提供浏览器任务的可视化调试模式，显示实时浏览器窗口

### Requirement 3

**User Story:** 作为任务配置者，我想要管理环境变量，以便在脚本中安全地使用敏感信息如账号密码

#### Acceptance Criteria

1. WHEN 用户访问环境变量管理页面 THEN 系统 SHALL 显示全局和任务级别的环境变量列表
2. WHEN 用户添加新的环境变量 THEN 系统 SHALL 加密存储敏感信息并提供变量名供脚本引用
3. WHEN 用户编辑环境变量 THEN 系统 SHALL 允许修改变量值并重新加密存储
4. WHEN 脚本执行时 THEN 系统 SHALL 将相关环境变量注入到执行环境中
5. WHEN 用户查看环境变量 THEN 系统 SHALL 隐藏敏感信息的实际值

### Requirement 4

**User Story:** 作为系统监控者，我想要查看详细的执行日志和截图，以便了解任务执行状态和排查问题

#### Acceptance Criteria

1. WHEN 任务执行时 THEN 系统 SHALL 实时记录所有操作日志到数据库，包含文本日志和截图
2. WHEN 用户点击任务的日志按钮 THEN 系统 SHALL 提供历史日志和实时日志两个选项
3. WHEN 用户查看历史日志 THEN 系统 SHALL 提供分页的日志列表，包含时间戳、日志级别、消息内容和相关截图
4. WHEN 用户查看实时日志 THEN 系统 SHALL 提供实时日志流功能，显示正在执行任务的实时输出
5. WHEN 用户查看浏览器任务日志 THEN 系统 SHALL 在日志中嵌入显示执行过程中的截图
6. WHEN 用户搜索日志 THEN 系统 SHALL 支持按时间范围、关键词、日志级别过滤
7. WHEN 日志文件过大 THEN 系统 SHALL 自动轮转和清理旧日志文件
8. WHEN 用户点击截图 THEN 系统 SHALL 提供截图放大查看功能

### Requirement 5

**User Story:** 作为任务负责人，我想要接收任务执行结果通知，以便及时了解任务状态

#### Acceptance Criteria

1. WHEN 任务执行完成 THEN 系统 SHALL 根据配置发送通知到指定渠道
2. WHEN 用户配置通知渠道 THEN 系统 SHALL 支持邮件、钉钉、企业微信、Server酱等多种通知方式
3. WHEN 任务执行失败 THEN 系统 SHALL 立即发送失败通知包含错误信息
4. WHEN 用户测试通知配置 THEN 系统 SHALL 提供测试通知发送功能
5. WHEN 通知发送失败 THEN 系统 SHALL 记录通知失败日志

### Requirement 6

**User Story:** 作为系统管理员，我想要管理用户权限，以便控制不同用户对系统功能的访问

#### Acceptance Criteria

1. WHEN 管理员管理用户 THEN 系统 SHALL 提供用户创建、编辑、删除功能
2. WHEN 分配用户角色 THEN 系统 SHALL 支持管理员、操作员、查看者等不同权限级别
3. WHEN 用户登录系统 THEN 系统 SHALL 根据用户角色显示相应的功能菜单
4. WHEN 用户执行操作 THEN 系统 SHALL 验证用户权限并记录操作审计日志
5. WHEN 用户权限不足 THEN 系统 SHALL 拒绝操作并显示权限不足提示

### Requirement 7

**User Story:** 作为系统运维人员，我想要监控系统状态，以便确保平台稳定运行

#### Acceptance Criteria

1. WHEN 用户访问系统监控页面 THEN 系统 SHALL 显示浏览器池状态、任务执行统计、系统资源使用情况
2. WHEN 浏览器实例异常 THEN 系统 SHALL 自动重启浏览器实例并记录异常日志
3. WHEN 系统资源不足 THEN 系统 SHALL 发送告警通知并限制新任务执行
4. WHEN 查看系统性能 THEN 系统 SHALL 提供任务执行成功率、平均执行时间等统计数据
5. WHEN 系统出现故障 THEN 系统 SHALL 自动记录故障信息并尝试自动恢复

### Requirement 8

**User Story:** 作为脚本开发者，我想要使用可视化编辑器编写 Dusk 脚本和转换青龙脚本，以便更高效地创建自动化任务

#### Acceptance Criteria

1. WHEN 用户编写脚本 THEN 系统 SHALL 提供代码编辑器支持 PHP 语法高亮和自动补全
2. WHEN 用户需要帮助 THEN 系统 SHALL 提供 Dusk API 文档和常用代码片段
3. WHEN 用户测试脚本 THEN 系统 SHALL 提供脚本调试功能，支持单步执行和断点调试
4. WHEN 脚本有语法错误 THEN 系统 SHALL 在保存前进行语法检查并提示错误位置
5. WHEN 用户导入脚本 THEN 系统 SHALL 支持从文件导入现有的 Dusk 测试脚本
6. WHEN 用户导入青龙脚本 THEN 系统 SHALL 提供青龙脚本转换功能，自动将JavaScript/Python脚本转换为Dusk脚本
7. WHEN 用户使用转换功能 THEN 系统 SHALL 分析青龙脚本的逻辑并生成对应的Dusk浏览器操作代码
8. WHEN 转换完成 THEN 系统 SHALL 自动填充任务表单，包含转换后的脚本内容和推荐的任务配置

### Requirement 9

**User Story:** 作为系统开发者，我想要使用命令行工具转换青龙脚本，以便批量迁移现有的自动化脚本

#### Acceptance Criteria

1. WHEN 开发者执行转换命令 THEN 系统 SHALL 提供 artisan 命令支持青龙脚本到Dusk脚本的转换
2. WHEN 指定输入文件 THEN 系统 SHALL 读取JavaScript或Python青龙脚本文件
3. WHEN 解析脚本内容 THEN 系统 SHALL 识别HTTP请求、DOM操作、延时等关键操作
4. WHEN 生成Dusk脚本 THEN 系统 SHALL 将青龙脚本逻辑转换为对应的Laravel Dusk语法
5. WHEN 转换完成 THEN 系统 SHALL 输出转换后的PHP Dusk脚本文件
6. WHEN 转换遇到不支持的操作 THEN 系统 SHALL 在输出中添加注释说明需要手动处理的部分

### Requirement 10

**User Story:** 作为任务设计者，我想要使用可视化拖拽方式创建工作流，以便无需编程知识就能设计复杂的自动化任务

#### Acceptance Criteria

1. WHEN 用户选择可视化工作流模式 THEN 系统 SHALL 提供拖拽式工作流设计器界面
2. WHEN 用户拖拽操作节点 THEN 系统 SHALL 提供预定义的操作组件，包括页面访问、元素点击、文本输入、条件判断、循环等
3. WHEN 用户连接节点 THEN 系统 SHALL 允许通过拖拽连线定义操作执行顺序和条件分支
4. WHEN 用户配置节点参数 THEN 系统 SHALL 为每个节点提供参数配置面板，支持选择器、文本、变量等输入
5. WHEN 用户预览工作流 THEN 系统 SHALL 提供工作流可视化预览和逻辑验证功能
6. WHEN 用户保存工作流 THEN 系统 SHALL 自动将可视化工作流转换为对应的Dusk脚本代码
7. WHEN 工作流包含错误 THEN 系统 SHALL 高亮显示错误节点并提供修复建议

### Requirement 11

**User Story:** 作为系统管理员，我想要优化任务运行性能，以便提高任务执行效率和资源利用率

#### Acceptance Criteria

1. WHEN 创建任务时 THEN 系统 SHALL 允许设置任务类型为独占或非独占模式
2. WHEN 独占任务执行时 THEN 系统 SHALL 确保该任务独占整个浏览器实例，不与其他任务共享
3. WHEN 非独占任务执行时 THEN 系统 SHALL 允许在同一浏览器实例中开启多个标签页并行执行
4. WHEN 任务配置主域名时 THEN 系统 SHALL 记录任务的主要操作域名，用于浏览器实例分配优化
5. WHEN 分配浏览器实例时 THEN 系统 SHALL 优先将相同域名的任务分配到同一浏览器实例
6. WHEN 监控任务性能时 THEN 系统 SHALL 显示每个浏览器实例的标签页使用情况和资源占用
7. WHEN 浏览器实例资源不足时 THEN 系统 SHALL 自动创建新的浏览器实例或等待现有实例释放

### Requirement 12

**User Story:** 作为任务配置者，我想要系统自动管理登录状态和Cookie，以便任务执行时无需重复登录提高执行效率

#### Acceptance Criteria

1. WHEN 任务配置包含账号信息时 THEN 系统 SHALL 提供用户名、密码、登录URL等认证信息配置字段
2. WHEN 任务首次执行登录时 THEN 系统 SHALL 自动保存登录成功后的Cookie信息到数据库
3. WHEN 任务再次执行时 THEN 系统 SHALL 优先使用已保存的Cookie进行免登录访问
4. WHEN Cookie失效时 THEN 系统 SHALL 自动检测登录失败并重新执行登录流程
5. WHEN 保存Cookie时 THEN 系统 SHALL 按主域名分组存储Cookie，支持跨子域名共享
6. WHEN 多个任务使用相同账号时 THEN 系统 SHALL 共享Cookie信息避免重复登录
7. WHEN Cookie即将过期时 THEN 系统 SHALL 提前刷新Cookie保持登录状态
8. WHEN 管理Cookie时 THEN 系统 SHALL 提供Cookie管理界面，支持查看、编辑、删除Cookie信息
9. WHEN 任务执行完成时 THEN 系统 SHALL 更新Cookie的最后使用时间和有效性状态

### Requirement 13

**User Story:** 作为系统用户，我想要任务能够智能处理登录和会话管理，以便减少因登录问题导致的任务失败

#### Acceptance Criteria

1. WHEN 任务访问需要登录的页面时 THEN 系统 SHALL 自动检测是否需要登录
2. WHEN 检测到登录页面时 THEN 系统 SHALL 自动使用配置的账号信息进行登录
3. WHEN 登录成功时 THEN 系统 SHALL 自动保存新的会话信息和Cookie
4. WHEN 遇到验证码时 THEN 系统 SHALL 记录验证码图片并暂停任务等待人工处理
5. WHEN 账号被锁定或异常时 THEN 系统 SHALL 记录异常信息并发送告警通知
6. WHEN 会话超时时 THEN 系统 SHALL 自动重新登录并继续执行任务
7. WHEN 多账号轮换时 THEN 系统 SHALL 支持配置多个账号并自动轮换使用
