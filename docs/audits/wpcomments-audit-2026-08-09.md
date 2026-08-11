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
| 评论验证 | `modules/class-validation-module.php`、`assets/js/validation.js` | 浏览器提示 + `preprocess_comment` 基于核心组装数据做服务端校验，兼容 REST/XML-RPC/程序化入口。 |
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
- 自定义表：每站点 `{$wpdb->prefix}wpcomments_reports`；数据库版本为 3，包含评论+IP、IP+日期复合索引。
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

## 9. [CX] 第三轮复核与修复

### 9.1 扫描分桶与文件级风险

第三轮没有降低规则、添加 baseline 或忽略第一方错误。原始结果与人工分桶如下：

| 工具 | 原始结果 | 第一方代码 | 打包依赖/生成物/开发文件 | 规则上下文 |
|---|---:|---|---|---|
| PHPCS WordPress-Extra | 11994 errors / 491 warnings | 生产代码 11988 errors / 491 warnings，主要是历史空白、命名、文档块、转义和 i18n | `build/index.asset.php` 6 errors；没有 vendor 第三方目录 | PHPCS 同一条记录可能同时含格式与语义，误报不从总数重复扣除 |
| PHPStan level 5 | 127 errors | 49 条真实类型、死 wrapper、错误 API 签名或未使用代码 | 0 条第三方 | 78 条静态上下文缺失：48 条入口常量、30 条 include 模板变量 |
| Plugin Check | 218 errors / 192 warnings | 发布代码 203 errors / 148 warnings | tests/构建辅助 15 errors / 44 warnings；发布 ZIP 不含这些文件 | 匿名核心评论提交 nonce、可信 `$wpdb->prefix` 表名和 include 上下文需人工判定 |

文件级 Top 风险及处理：

1. `includes/class-wpcomments-settings.php`、`includes/class-wpcomments-network-settings.php`：统一 unslash/sanitize、权限/nonce、safe redirect、输出转义和 i18n；网络统计改为明确的当前站点统计。
2. `includes/modules/class-module-*.php`：原 wrapper 未实现完整接口并调用不存在方法；改为有弃用标记、委托真实模块的兼容 wrapper，模块管理器保留兼容回退。
3. `wpcomments.php`：修正 `register_setting()` 参数；迁移函数只有在 schema、索引和数据回填全部成功后才写数据库版本；删除 `CREATE TABLE IF NOT EXISTS`，因为该形式会使 `dbDelta()` 无法升级旧表。
4. `includes/class-wpcomments-email-notification.php`、`templates/email-*.php`：补 ID 类型、nonce/input 清洗、模板变量契约和最终输出转义。
5. `includes/class-wpcomments-frequently-replies.php`：数组输入逐项清洗；修正 style 依赖参数类型和整数属性输出。

最终扫描：

- PHPCS WordPress-Extra（排除不会进入发布包的 tests/build）：**NOT PASS**，11950 errors / 490 warnings（36 files）；仍以历史格式和文档块为主，不能作为发布绿灯。
- PHPStan level 5 + WordPress extension：**PASS**。整组扫描受单进程 1 GiB 内存限制中止，因此使用完全相同配置逐文件扫描 36 个发布 PHP 文件；36/36 exit 0，0 errors。静态 bootstrap 只补 WordPress/插件入口常量和模板变量契约，没有忽略错误。
- Plugin Check 源码树：**NOT PASS**，开发测试文件仍会报告错误；发布范围已达到 **0 errors / 92 warnings**。测试、docs 和 `.distignore` 均不进入 ZIP。

### 9.2 破坏性迁移 fixture

所有迁移测试均使用独立 Compose project、独立 MariaDB volume，数据库名强制以 `wpcomments_test_` 开头；`tests/wp-migration-smoke.php` 同时检查显式环境开关，不满足条件时 exit 2。没有连接或修改生产数据库。

| 场景 | 环境 | 结果 |
|---|---|---|
| 单站旧表升级 | WordPress 5.9.3 / PHP 8.0.30 / disposable MariaDB | PASS：旧列升级、索引、日期回填、2 行历史数据保留 |
| 重复升级 | 同上 | PASS：schema 与行数不变 |
| 失败保护与重试 | 同上 | PASS：强制 schema 验证失败时版本保持 1，解除失败后可重试到 2 |
| 多站旧表升级 | WordPress 5.9.3 / PHP 7.4.33，2 sites | PASS：两个站点分别完成升级、幂等、失败保护和重试 |

MySQL DDL 没有跨 `dbDelta()` 的事务回滚；本修复提供的是“失败不推进版本 + 可重试”的逻辑回滚，不把它描述为数据库事务回滚。

### 9.3 最低兼容矩阵与浏览器

| WordPress | PHP | 单站 runtime | 邮件 | disable + REST/XML-RPC | multisite |
|---|---|---|---|---|---|
| 5.9.3 | 7.4.33 | PASS | PASS，截获 pending/approved 2 封 | PASS | PASS，2 sites；同时通过迁移 fixture |
| 5.9.3 | 8.0.30 | PASS | PASS，截获 pending/approved 2 封 | PASS | SKIP；多站点由 PHP 7.4 单元覆盖 |
| 7.0.2 | 8.3.33 | PASS（第二轮及第三轮回归） | PASS | PASS | PASS（第二轮） |

Playwright 使用真实 Chromium 登录一次性 WordPress，打开 `options-general.php?page=wpcomments-settings`：页面标题和“WPComments 设置 版本: 2.0.0”可见，控制台只有 WordPress 自带的 JQMIGRATE log，无 warning/error；第三轮判定 PASS。第二轮的举报、私密、角色徽章、字符限制、置顶和完全禁用浏览器流程仍保留为功能证据。

### 9.4 发布包、版本与卸载

- 发布包由 `tests/build-package.sh` 构建并通过 `unzip -t`；最终 SHA-256 为 `c5ae7c4f96ab1859ba512bc27f8f2678002d23eb0874e724a6ee2438fcc5ea35`。
- ZIP 清单不含 `.git`、`.github`、tests、docs、vendor、node_modules、Composer/npm 文件、PHPCS/PHPStan 配置、日志、SQL、私钥或 `.env`。
- 插件头 `Version: 2.0.0`、`Requires PHP: 7.4`；readme `Stable tag: 2.0.0`、`Requires at least: 5.9`、`Requires PHP: 7.4`，三者一致。
- 数据库升级路径为 `WPCOMMENTS_DB_VERSION=2`，旧表真实 fixture 已通过。
- 默认卸载保留测试 PASS；只有明确 opt-in 才删除站点/网络数据。

### 9.5 仍未关闭的发布门闸

1. PHPCS WordPress-Extra 仍有大规模历史格式/文档债务，**NOT PASS**。
2. Plugin Check 发布范围虽然 0 errors，但仍有 warnings，**NOT PASS**；主要是匿名评论入口 nonce、可信动态表名/直接 DB 查询、前缀和商标提示，需继续逐条判定或修复。
3. 仓库没有 GitHub Actions workflow，PR head 没有可见 check run/job；CI **NOT PASS**，不能用 GitHub combined status 的 pending 代替实际 job。
4. FeiCode 仍缺对应仓库；GitHub 是用户指定真源，但内部镜像/备份治理未闭环。
5. 未创建 tag/release、未发布、未部署、未合并 PR。

### 9.6 PR #1 CI 实际状态

2026-08-09 针对 head `ddc7d71ab3b5a2f2f550eda0abf7547ef3f17ec2` 查询 GitHub REST API：PR 为 Open、未合并；combined status=`pending`，但 `total_count=0`、statuses 为空；check runs=0、check suites=0、该 head workflow runs=0。仓库没有 workflow，因此没有可逐项列出的 CI job；此处判定 **NOT PASS（无 CI 样本）**，不是“CI 正在通过”。

## 10. [CX] 第四轮发布门收口

### 10.1 92 条 Plugin Check warnings 与 PHPCS 分桶

第四轮对第三轮的原始结果逐条分桶；发布范围没有 vendor 第三方依赖，也不含 tests、docs、`.github` 或构建配置，因此这些结果均来自第一方运行时代码/readme，不把开发文件或第三方问题算给产品代码。

| 工具/来源 | 第一方代码 | 构建产物/开发文件 | 第三方 | 判定 |
|---|---:|---:|---:|---|
| Plugin Check（第三轮） | 92 warnings | 0 | 0 | 38 nonce/input 上下文、30 DB、14 全局前缀、6 WordPress.org、4 comment-meta 性能 |
| Plugin Check（第四轮修复后发布范围） | 76 warnings / 0 errors | 0 | 0 | **NOT PASS**；移除 2 个重复 textdomain loader，并给 14 个入口/卸载全局变量加插件前缀 |
| PHPCS WordPress-Extra（修复后） | 11926 errors / 490 warnings，36 files | 0（tests/build/vendor 已排除） | 0 | **NOT PASS**；12298 项可自动修复，主体是历史缩进/括号空格，不为清零做全仓格式改写 |

PHPCS 规则 Top：`SpacesUsed` 5720、函数调用左右括号空格各 1741、行尾多余空格 386、控制结构括号空格各 340、数组箭头对齐 264。文件 Top：`includes/class-wpcomments-settings.php` 2223/87、`includes/class-wpcomments-email-notification.php` 1521/95、`includes/class-wpcomments-network-settings.php` 1350/56、`includes/class-wpcomments-frequently-replies.php` 877/65、`includes/class-wpcomments-disable-mode.php` 676/9。

剩余 76 条 Plugin Check warnings：

- 38 条 nonce/input：公开评论提交钩子没有插件自建 nonce，后台保存入口则已在上游完成 capability + nonce；2 条 replies 数组在下一步逐项 sanitize。这些不是已证明的权限绕过，不能为了扫描清零破坏原生评论、REST 或程序化评论兼容。
- 30 条 DB：表名都来自可信 `$wpdb->prefix`，值参数使用 prepare/insert；WordPress 5.9 不支持 `%i` 标识符占位。仍是 WordPress.org review debt，不判 SQL 注入 PASS。
- 4 条订阅列表 meta query：是真实的大站性能债务，需在 2.1 的订阅索引/独立表方案处理。
- 4 条 WordPress.org：3 条 `WPComments/wpcomments` 受限商标/slug，1 条为兼容最低 WordPress 5.9 保留的主入口 `load_plugin_textdomain()`。重命名会影响 slug、更新、Text Domain 和数据迁移，属于产品/发布决策，本轮不擅自改名。

PHPCompatibilityWP 7.4+ 为 **PASS**；这两份扫描没有证明新的 PHP 未来兼容 fatal/deprecation。PHPStan level 5 在固定依赖、单进程、2 GiB 上整组 **PASS，0 errors**；为了避免静态 bootstrap 把网络常量折叠成单一分支，网络状态展示通过返回 `bool` 的内部方法保留单站/多站分析路径，没有加入错误 baseline 或 blanket ignore。

### 10.2 最终代码与真实 WordPress 矩阵

- WordPress 7.0.2 / PHP 8.3.33 单站 disposable fixture：runtime、邮件 pending/approved、disable、REST、XML-RPC 全部 PASS，命令 exit 0。
- WordPress 7.0.2 / PHP 8.3.33 两站 multisite disposable fixture：表/cron/网络覆盖 PASS，命令 exit 0。
- 第三轮的 WordPress 5.9.3 / PHP 7.4.33 与 PHP 8.0.30 结果继续保留；第四轮没有用 PHP 8.3 单一组合替代最低兼容证据。
- `tests/run-ci-core.sh` 使用独立 Compose project、随机宿主端口和 disposable volumes；结束时 `down -v`，不连接生产数据库。

### 10.3 最小 GitHub Actions 与发布 ZIP

- 新增 `.github/workflows/ci.yml`，jobs 为 `lint`、`php-compatibility`、`phpstan`、4-cell `wordpress-core`、`release-zip`。
- Actions 固定 major：`actions/checkout@v4`、`shivammathur/setup-php@v2`；Composer lock 固定 PHPCS 3.13.6、PHPCompatibilityWP 2.1.8、PHPStan 2.2.8（传递依赖）、phpstan-wordpress 2.0.3、WPCS 3.4.1。所有 jobs 只需 `contents: read`，不使用生产 secret。
- `tests/check-package.sh` 构建后显式拒绝 `.git`、`.github`、docs、tests、vendor、node_modules、Composer/npm/PHPCS/PHPStan 文件、`.env` 和 HANDOFF；审计报告和交接文档不会进入 ZIP。
- 插件头 `2.0.0`、readme Stable tag `2.0.0`、最低 WordPress `5.9`、PHP `7.4` 保持一致；默认卸载仍保留数据。

### 10.4 发布门

| 门闸 | 结果 | 依据 |
|---|---|---|
| 代码测试 | **PASS** | PHP lint/static contracts；WP 7.0.2/PHP 8.3 单站+多站；保留 WP 5.9.3/PHP 7.4/8.0、迁移和 E2E 结果 |
| 静态检查 | **NOT PASS** | PHPCompatibility/PHPStan PASS；Plugin Check 0 errors/76 warnings、PHPCS 11926/490 未清 |
| 自动 CI | **PASS** | implementation head `b28da3f` 的 push/PR 两个 Actions runs（31311707979、31311708525）均完成；16/16 check runs success |
| FeiCode 真源治理 | **NOT PASS** | GitHub 是用户指定真源；FeiCode 仍没有可见对应仓库/镜像 |

implementation head `b28da3f6100e9aec39763291b0093acc6f71bbdb` 的两个 workflow runs 均完成：lint、PHPCompatibility、PHPStan、release ZIP、WP5.9/PHP7.4、WP5.9/PHP8.0、WP7.0.2/PHP8.3 单站与多站共 16/16 check runs 为 success。最终测试 ZIP `/tmp/wpcomments-fourth-final.zip` 为 66 entries，SHA-256 `5067d43ea6348edd5f7ba5982b082ae3cbd31322f01ec645a5d133aee8f7f86c`。

PR #1 继续保持 Open；未合并、未发布、未部署。WordPress.org slug 重命名、订阅大站索引以及剩余 DB/nonce review debt 是仍未关闭的发布阻断项。


## 11. [CX] 第五轮本地发布候选收口（2026-08-11）

### 11.1 确定性修复与保留边界

本轮没有删除用户功能，也没有改变既有 option、hook、comment meta、举报表后缀或卸载默认保留策略。

1. 插件展示名和 readme 标题改为 **WenPai Comments**，描述移除受限名称；最低 WordPress/PHP、版本号和 Stable tag 保持 `5.9 / 7.4 / 2.0.0`。最低 WordPress 5.9 仍需仓库内中文语言包，因此改用明确 locale 的 `load_textdomain()`，不删除打包翻译。
2. 评论验证和字符限制改读 `preprocess_comment` 的核心组装数据，不再直接读匿名评论 `POST`，普通评论、REST、XML-RPC 与程序化 `comment_post` 不被插件 nonce 阻断。
3. 私密、回复订阅和 GDPR 自定义字段共用评论上下文 nonce；只有提交插件自定义字段时才在插入前验证。持久化位置再次校验，伪造字段不能留下已插入评论或写入 meta。真实浏览器测试还发现登录态订阅复选框重复渲染，现已区分登录/匿名字段入口并保证过滤器幂等。
4. 举报表名由固定后缀加实际 `$wpdb->prefix` 组成，并在 SQL 前限制为 `[A-Za-z0-9_]+`；WordPress 5.9 没有 `%i`，因此每个 schema/live anti-abuse 查询只做逐行带理由豁免。后台列表缓存 60 秒并在写入/清理时失效；重复和频率判断保持实时。`COUNT(*)` 改为 `SELECT 1 ... LIMIT 1`，数据库版本升为 3，新增 `(comment_id,user_ip_hash)` 与 `(user_ip_hash,report_date)` 索引。
5. 回复订阅只存正向 `on`；未勾选和退订删除 meta。v3 升级清除旧 `off` 行，列表不再按 `meta_value` 扫描。保留 comment meta 数据模型以兼容既有站点，本轮不为扫描清零引入新订阅表。
6. 设置保存入口在 capability + tab nonce 通过后只 unslash 一次，再把 request 传给各保存函数；GET 排序/标签只用 allowlist/filter input。常用回复数组仍逐字段 `sanitize_text_field`/`wp_kses`，只在准确数据流行使用 PHPCS 注释。

WordPress.org 对包根 slug `wpcomments` 仍有 1 条受限词 warning。改 slug 会改变插件 basename、Text Domain、更新身份和升级冲突边界；在 FeiCode canonical 建立及新 slug/迁移方案获人工批准前不擅自改包身份。因此它仍是发布阻断，不伪装成已解决。

### 11.2 静态检查与增量门

| 检查 | 命令摘要 | 结果 |
|---|---|---|
| PHP lint | `find ... -name '*.php' ... php -l` | **PASS**，exit 0 |
| 静态契约 | `tests/static-contracts.sh` | **PASS**，exit 0 |
| PHPCompatibilityWP | `phpcs --standard=PHPCompatibilityWP --runtime-set testVersion 7.4-` | **PASS**，exit 0 |
| PHPStan | `phpstan analyse --configuration=phpstan.neon.dist --memory-limit=2G` | **PASS**，0 errors，exit 0 |
| release PHPCS | `tests/release-phpcs.sh` | **PASS**，30 个相对 `origin/main` 变更的第一方运行 PHP 文件，exit 0 |
| Plugin Check 2.0.0 | `tests/run-plugin-check.sh /tmp/wpcomments-r5-plugin-check-ultimate.csv` | **NOT PASS**，0 errors / 1 warning，命令 exit 0；唯一剩余为包 slug `wpcomments` |
| 全仓 WordPress-Extra | 排除 tests/build/vendor，JSON report | **NOT PASS**，12090 errors / 477 warnings，exit 2；仍是第一方历史格式/文档债务 |

`phpcs.release.xml.dist` 只启用 PHPCompatibilityWP、Security、DB、capability、deprecated 与危险 PHP API 等发布风险规则；`tests/release-phpcs-files.txt` 是 30 文件显式清单。runner 会拒绝遗漏的变更运行 PHP、空清单和 `phpcs:disable/phpcs:set`；没有 baseline、目录级 blanket ignore 或为清零进行全仓机械格式化。DB 只在已验证标识符且必须实时/直接访问的具体行使用 scoped ignore。

Plugin Check 最终 CSV SHA-256 为 `433e947dfd7a9750d264642ec93b4978b0d45b693267304653cbde8f68afbfcf`；全仓 WordPress-Extra JSON SHA-256 为 `dd51dd45f52ed5ef7a553d79affb66904c191d08d1ae69b4f90156db61fafb57`。

### 11.3 disposable WordPress、迁移与浏览器

所有数据库和浏览器测试使用独立 Compose project、随机宿主端口和一次性 volume，结束执行 `down -v`；没有连接生产数据库。

| 环境/入口 | 覆盖 | 结果 |
|---|---|---|
| WP 5.9.3 / PHP 7.4.33 单站 | runtime、邮件、完全禁用、REST、XML-RPC | **PASS**，exit 0 |
| WP 5.9.3 / PHP 8.0.30 单站 | 同上 | **PASS**，exit 0 |
| WP 7.0.2 / PHP 8.3.33 单站 | 同上，含 v3 schema/index | **PASS**，exit 0 |
| WP 7.0.2 / PHP 8.3.33 multisite（2 sites） | 每站存储、网络覆盖、cron | **PASS**，exit 0 |
| WP 5.9.3 / PHP 8.0 单站迁移 | 旧表、2 行保留、复合索引、清理 `off` meta、重复升级、强制失败不推进、重试 | **PASS**，exit 0 |
| WP 5.9.3 / PHP 7.4 multisite 迁移（2 sites） | 每站同一破坏性 fixture | **PASS**，exit 0 |
| WP 7.0.2 / PHP 8.3 + Chromium | 设置页、置顶、角色徽章、评论 nonce、私密+订阅真实提交、匿名举报、console、DB meta/status/table | **PASS**，exit 0；private=3、sticky=2、reports=1 |

迁移的“回滚”仍是失败不推进 DB version 并允许重试，不宣称 MySQL DDL 跨 `dbDelta()` 的事务回滚。版本化入口为 `tests/run-migration-ci.sh` 与 `tests/run-e2e.sh`；E2E 固定 Playwright `1.58.2`，不是沿用历史截图。

### 11.4 发布包与 CI

`tests/build-package.sh` 现在按排序文件清单、统一 mtime 和 `zip -X` 构建；`tests/check-package.sh` 明确拒绝 `.github`、tests、docs/HANDOFF、vendor、node_modules、Composer/npm、两个 PHPCS 配置、PHPStan、`.env` 和临时文件。治理文档不会进入发布 ZIP。

本节首次提交时 GitHub Actions 状态为 **PENDING**；只有同一 head 的 push 与 pull_request 两个 run 都完成、8 个唯一 job 各出现两次并形成 16/16 success，才更新为 PASS。PR #1 必须保持 Open/unmerged。

### 11.5 FeiCode 真源与 Board 人工动作

完整未执行草案见 `docs/governance/wpcomments-feicode-import.md`。它只申请：创建空、未初始化、`mirror=false` 的 FeiCode `WenPai-org/wpcomments`；导入全部 heads/tags；用双方 fsck、refs manifest 和 reachable-object manifest 验证；设置 default `main` 与 W1 禁直推保护。GitHub `refs/pull/*` 只归档证据，不写 Forgejo 保留 refs。

切换后 FeiCode 才是 canonical；未来持续镜像需另开 change，只允许 FeiCode → GitHub 单向 push，禁止反向 pull 或双向循环。Board 草案必须由 devops 发布和人工审批，本轮没有执行 Board 命令、创建 FeiCode 仓库、镜像、tag/release、合并或部署。

### 11.6 发布门

| 门闸 | 结果 | 未关闭项 |
|---|---|---|
| 功能/迁移/E2E | **PASS** | 以上均为非生产 disposable 样本 |
| 发布风险增量静态门 | **PASS** | 30 文件 release PHPCS、PHPCompatibility、PHPStan 均绿 |
| WordPress.org 接收 | **NOT PASS** | 包 slug `wpcomments` 仍有受限词 warning，需要新 slug/身份迁移人审 |
| 全仓历史 PHPCS | **NOT PASS** | 12090 errors / 477 warnings，不以本轮 scoped gate冒充清零 |
| GitHub 自动 CI | **PENDING** | 等候候选提交后两个 runs 的 16 个真实 checks |
| FeiCode 真源治理 | **NOT PASS** | Board change 尚未审批/执行，FeiCode canonical 尚未建立 |

PR #1 继续保持 Open；禁止合并、发布、部署。
