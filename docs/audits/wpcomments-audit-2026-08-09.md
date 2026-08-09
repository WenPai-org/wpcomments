# WPComments 2.0.0 全面审计（2026-08-09）

> [CX] 审计基线：`WenPai-org/wpcomments@50fe5662fdf987622767abdfafb1a599a27d918f`。隔离分支：`codex/wpcomments-audit-hardening`。未部署、未发布、未合并默认分支。

## 1. 源码与治理边界

用户指定 GitHub 仓库为本次代码真源。Mac 真实部署备份与该提交逐文件一致：双方 54 个文件，清单 SHA-256 均为 `583de86e8cb8cf5e32ecc0d3030b3c431e7f402136763c371fc72c2167a1e05f`，主文件 SHA-256 均为 `12da280e491690474f00f9f4395fbb06ebf90b11b69744ad9c8c216ba6511d3c`。FeiCode 未找到对应仓库，仍是镜像、备份和权限治理问题，但不再阻塞本次审计。GitHub 当时只有 `main`，无 tag、release、fork、Actions workflow 或 artifact，基线提交未签名。

## 2. 入口、功能、数据流与依赖

### 2.1 生命周期和入口

- `wpcomments.php`：插件加载、语言包、数据库升级、激活/停用、新站点初始化、举报清理 cron、通用设置字段。
- `includes/class-wpcomments-bootstrap.php`：解析单站/网络有效设置；在“完全禁用评论”和“评论增强”两种路径之间分流。
- `includes/class-wpcomments-core.php`、`includes/class-wpcomments-modules.php`：加载、注册和启动启用的模块。
- `includes/class-wpcomments-settings.php`、`includes/class-wpcomments-network-settings.php`：站点和网络设置入口、保存与覆盖策略。
- `includes/class-wpcomments-disable-mode.php`：前台、后台、REST、XML-RPC、评论 feed、区块和管理入口的禁用路径。

### 2.2 功能清单

| 功能 | 入口与存储 | 主要数据流 |
|---|---|---|
| 完全禁用评论 | `class-wpcomments-disable-mode.php` | 过滤评论开放状态、REST/XML-RPC 方法、评论查询和后台入口；不删除既有评论。 |
| 删除待审核评论 | `modules/class-delete-pending-module.php` | 管理页按权限和 nonce 删除 `hold` 评论。 |
| 移除网站字段 | `modules/class-remove-website-field-module.php` | 过滤评论表单字段。 |
| 评论验证 | `modules/class-validation-module.php`、`assets/js/validation.js` | 浏览器提示 + `pre_comment_on_post` 服务端校验。 |
| 私密评论 | `modules/class-private-comments-module.php` | 表单勾选写入 comment meta `wpcomments_private`；过滤内容与回复权限。 |
| 字符限制 | `modules/class-character-limiter-module.php`、`assets/js/comment-limiter.js`、`assets/css/comment-limiter.css` | `wpcomments_comment_limiter_options` 同时驱动 DOM、浏览器计数和服务端上限。 |
| 举报 | `modules/class-comment-reporting-module.php`、`assets/js/comment-report.js` | AJAX → 权限/nonce/公开评论校验 → 频率/重复限制 → `{$prefix}wpcomments_reports` → 后台列表/清理 cron。 |
| 置顶 | `modules/class-sticky-comments-module.php` | 后台行操作写入 `wpcomments_sticky`，评论查询优先排序。 |
| 角色徽章 | `modules/class-role-badge-module.php` | 依据评论作者用户角色生成转义后的徽章 HTML/CSS。 |
| 公安备案辅助 | `modules/class-gongan-beian-module.php` | 评论写入 `yjwgaba_ipport`，后台显示 IP/端口/时间。该项涉及个人数据，应保持可选。 |
| 邮件/回复订阅 | `includes/class-wpcomments-email-notification.php`、`templates/` | 评论插入/批准 → 管理员通知或父评论订阅者通知；comment meta `wpcomments_subscribe_to_comment`；带签名退订。 |
| 订阅管理 | `class-wpcomments-subscriptions-table.php` | 分页查询订阅 comment meta，后台查看/删除。 |
| 常用回复 | `class-wpcomments-frequently-replies.php` | 管理设置/AJAX 保存 `wpcomments_frequently_replies`，评论编辑器调用。 |
| 审核修改信息 | `class-wpcomments-moderation-info.php` | `comodinfo_comment_revisions` 保存最多 20 条修订记录并显示修改者/时间。 |

### 2.3 数据与外部依赖

- WordPress options/site options：功能开关、网络强制/覆盖规则、邮件、举报、字符限制和隐私设置。
- Comment meta：`wpcomments_private`、`wpcomments_sticky`、`wpcomments_subscribe_to_comment`、`comodinfo_comment_revisions`、`yjwgaba_ipport`。
- 自定义表：每站点 `{$wpdb->prefix}wpcomments_reports`；数据库版本为 2。
- 外部运行依赖仅为 WordPress/PHP/数据库和站点邮件传输；无第三方 HTTP API、远程更新器、凭据或 SSRF 入口。构建产物依赖已随插件打包的 WordPress 区块脚本。

## 3. 已确认并修复的问题

本次未确认 P0。以下 P1/确定性问题已在隔离分支修复：

1. **设置键失配导致功能表面启用、运行时失效或全部默认开启。** Bootstrap 现在传递站点/网络有效设置；可选增强默认关闭；邮件、常用回复、审核信息仅按开关实例化。
2. **举报功能的表结构、PHP、JS、CSS 和本地化对象互不一致。** 统一实际资源、DOM 和 AJAX 契约；校验公开且已批准的评论；增加登录限制、重复/频率限制；只读取可过滤的 `REMOTE_ADDR` 并仅存散列；管理查询有界。
3. **旧举报表无法可靠升级。** 数据库版本升为 2，保留兼容字段并增加 `user_ip_hash`、`reason`、`report_date` 和索引；迁移 `report_time`；网络新站点自动建表。
4. **卸载默认删除用户数据。** 现在默认保留；仅常量或明确选项 opt-in 才删除。多站点必须网络选项或常量授权，站点 UI 不得静默开启全网删除。
5. **邮件通知错过待审评论、批准后重复、操作链接无 nonce/状态错误、模板有死退订链接。** 待审和已批准新评论各发一次管理员邮件；批准只触发订阅回复逻辑；操作使用 WordPress nonce，已批准邮件不再显示“批准”；删除遵循回收站设置；管理设置替代死链接；fallback 内容全部转义。
6. **字符限制读取错误选项且前后端语义漂移。** 统一保存键、限制、提示、DOM 和管理员豁免语义。
7. **角色徽章前台调用命名空间内不存在的 `load_textdomain()`，会触发 fatal。** 改为全局函数并重置循环状态，资源和输出正确加载/转义。
8. **评论过滤器可能收到空 comment 对象。** 举报链接和审核修改信息增加 `\WP_Comment` 类型保护；浏览器提交不再出现 warning。
9. **订阅、常用回复、设置和审核历史存在权限、nonce、未界定输入/输出或无界查询。** 增加权限/nonce/清洗/转义、分页、排序白名单和修订上限。
10. **停用和多站点生命周期不完整。** 网络停用清除每站点清理任务；网络激活后的新站点创建表和 cron。
11. **发布包混入开发文件且文档指向错误仓库。** 增加 `.distignore` 和可复现打包脚本，修正文档链接与兼容声明。

## 4. 多站点、REST/XML-RPC 与卸载

- 网络激活时遍历站点建表/调度 cron；`wp_initialize_site` 为新站点补初始化。
- `wpcomments_network_enforce`、`wpcomments_allow_site_override` 和受控选项清单共同决定有效设置；UI 默认值已与运行时“可选功能默认关闭”一致。
- 完全禁用模式阻断评论 REST route、XML-RPC 评论方法、评论 feed/查询/表单和相关后台界面，但保留数据。
- 停用只清 cron，不删数据。卸载默认保留全部数据；显式 opt-in 后按站点删除 options、comment meta、自定义表和网络选项。

## 5. 验证记录

所有动态测试都在 WenPai VM 的隔离 Docker WordPress 中执行（WordPress 7.0.2、PHP 8.3.33、MariaDB；`http://localhost:8895`），不是生产站点。

| 检查 | 命令/结果 | 判定 |
|---|---|---|
| PHP 语法 | `find . -name '*.php' ... php -l`，exit 0 | PASS |
| 静态契约 | `bash tests/static-contracts.sh`，exit 0 | PASS |
| WordPress 运行 | `wp eval-file tests/wp-runtime-smoke.php` | PASS，模块按设置加载 |
| 完全禁用/REST/XML-RPC | `wp eval-file tests/wp-disable-smoke.php` | PASS |
| 邮件 | `wp eval-file tests/wp-email-smoke.php` | PASS，待审/已批准邮件 2 条，nonce 与状态动作已断言 |
| 多站点 | `wp eval-file tests/wp-multisite-smoke.php` | PASS，2 个站点；网络覆盖和存储验证 |
| 网络停用 | 网络停用脚本 | PASS，所有站点清理 cron 已清除后重新激活 |
| 卸载默认路径 | 直接包含 `uninstall.php` 的保留测试 | PASS，数据保留 |
| 举报真实 AJAX/DB | 首次 200、重复拒绝、未批准评论 403；DB 行含 64 字符 IP hash 和一致日期 | PASS |
| 浏览器 E2E | Playwright Chromium：举报、私密评论提交、角色徽章、字符限制、置顶/取消置顶状态、完全禁用页面 | PASS；真实提交曾发现并修复空对象 warning，复测无 warning |
| 置顶 DB | `wp comment meta get 7 wpcomments_sticky` | PASS，值为 `1`；后台显示“取消置顶” |
| PHPCompatibilityWP | PHP 7.4+ | PASS，exit 0 |
| 打包 | `bash tests/build-package.sh` + `unzip -t` | PASS；最终 SHA-256 `f9e9fa31d46befd5ebc205f4a79ea70660b8844a375b73a290b2d491257dd858` |
| PHPCS WordPress-Extra | 排除 tests 后 exit 2，11994 errors / 491 warnings | **NOT PASS**；大量历史格式、转义、i18n 债务 |
| PHPStan level 5 | WordPress extension，exit 1，127 errors | **NOT PASS**；死 wrapper、常量配置和类型债务 |
| Plugin Check 2.0.0 | 命令 exit 0，但 218 ERROR / 192 WARNING | **NOT PASS**；exit 0 不代表检查通过 |
| 旧表迁移集成 | 需要破坏性 `DROP TABLE` 构造样本，被 VM L2 门闸阻止 | **未执行**；只有静态和新安装证据，不写 PASS |

## 6. 尚未解决的问题

1. `includes/modules/*` 是未接入运行时的重复 wrapper；部分未实现接口且调用不存在的 Core 方法，是 PHPStan 的主要真实错误源。需先确认外部兼容承诺，再在一个版本的弃用窗口后移除。
2. 站点/网络设置页仍有大量历史转义、i18n 和格式问题；Plugin Check/PHPCS 不能作为发布门闸通过。
3. 私密评论进入待审后可在后台看到，但普通前台评论查询会先排除待审评论；“作者/管理员是否应在前台看到自己的待审私密评论”需要产品决策。
4. 网络统计文字称全网总数，但查询只覆盖当前 blog 表，应改名或按站点汇总。
5. 网络“同步/导出/导入”按钮没有完整可信后端，不能宣称可用。
6. 没有 updater、release workflow、tag 或 release；未发现 updater 冲突，但也没有可审计的发布/回滚自动化。
7. Plugin Check 的商标/slug 警告和大量输出转义债务仍需单独版本处理。
8. FeiCode 缺仓导致 GitHub 与内部备份关系不可自动验证，需补镜像/只读归档或明确废弃登记。

## 7. 功能拆分与版本方向

### 保留为核心

- 完全禁用评论及 REST/XML-RPC/后台一致行为。
- 站点/网络有效设置与兼容迁移。
- 私密、置顶、举报的最小实现，以及数据库生命周期/隐私/清理策略。
- 服务端验证和移除网站字段（低依赖、直接改善评论表单）。

### 可选模块

- **互动通知**：邮件订阅、管理员邮件、订阅管理。
- **编辑效率**：常用回复、审核修订信息、删除待审评论。
- **展示增强**：角色徽章、字符限制。
- **合规/地域扩展**：公安备案 IP/端口记录；必须显式开启并说明数据保留。

### 精简/废弃候选

- 一个兼容窗口后移除死 `includes/modules/*` wrapper。
- 在未实现前隐藏“同步/导出/导入”，不要保留误导按钮。
- 迁移覆盖足够版本后再移除举报表旧列；不能直接删历史数据。
- 公安备案功能不进入通用核心，保留为独立 opt-in 模块。

### 建议路线

- **2.0.1**：只交付本次确定性修复、数据库升级、保守默认值、真实运行测试和发布门闸基线，不新增功能。
- **2.1**：统一设置 schema 和 effective-setting API；把邮件/常用回复/审核信息纳入模块接口；处理 wrapper 弃用；补旧表升级 fixture、全网统计和真实导出。
- **2.2**：以 Plugin Check/PHPCS/PHPStan 的可执行基线为门闸，逐页修复转义/i18n，建立 tag、签名包、升级/回滚矩阵。

## 8. 隔离交付

- 首个修复提交：`4c12d16`（`Audit and harden WPComments feature suite`）。
- 分支：`codex/wpcomments-audit-hardening`。
- Pull Request：<https://github.com/WenPai-org/wpcomments/pull/1>。
- PR 未合并，未创建 tag/release，未部署任何站点。
