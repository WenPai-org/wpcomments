# HANDOFF

## 2026-08-09 — [CX] WPComments 2.0.0 审计与加固

- 基线：`50fe5662fdf987622767abdfafb1a599a27d918f`；用户指定 `https://github.com/WenPai-org/wpcomments` 为真源。
- 分支：`codex/wpcomments-audit-hardening`；只允许 PR，不发布、不合并。
- Mac 部署备份与基线逐文件一致；FeiCode 未找到对应仓库，保留治理风险。
- 修复：设置/模块默认值、举报 schema/AJAX/隐私、卸载默认保留、邮件待审通知与 nonce、字符限制、角色徽章 fatal、空 comment guard、权限/nonce/转义/分页、多站点生命周期和发布包。
- 动态环境：WenPai VM，Docker WordPress 7.0.2 / PHP 8.3.33 / MariaDB，端口 8895；未触碰生产。
- PASS：全 PHP lint、静态契约、WordPress runtime、禁用评论/REST/XML-RPC、邮件、2 站点网络覆盖、网络停用 cron、默认卸载保留、举报 AJAX/DB、Playwright E2E、PHPCompatibilityWP、zip 完整性。
- NOT PASS：PHPCS（11994 errors / 491 warnings）、PHPStan level 5（127 errors）、Plugin Check（218 ERROR / 192 WARNING）。零样本未记 PASS。
- 未执行：旧举报表 destructive fixture 迁移，因 VM L2 拒绝 `DROP TABLE`。新安装和静态迁移契约已验证。
- 未完成产品决策：前台待审私密评论可见性；全网统计语义；未实现的同步/导出/导入；死 `includes/modules/*` wrapper 的弃用窗口。
- 完整证据与拆分路线：`docs/audits/wpcomments-audit-2026-08-09.md`。
- 最终审计包：`/tmp/wpcomments-2.0.0-audit-final.zip`，SHA-256 `f9e9fa31d46befd5ebc205f4a79ea70660b8844a375b73a290b2d491257dd858`；仅测试产物，未发布。
- Git 交付：提交 `4c12d16` 已推送到 `codex/wpcomments-audit-hardening`；PR `https://github.com/WenPai-org/wpcomments/pull/1` 已创建，未合并。

## 2026-08-09 — [CX] 第三轮扫描、迁移与兼容复核

- 同一隔离分支继续修复；PR #1 保持 Open，未合并、发布或部署。
- 旧举报表迁移在 disposable single-site 与 2-site multisite 真实 MariaDB fixture 通过；覆盖旧列升级、历史行保留、重复幂等、强制失败不推进版本和解除失败后重试。
- 修复 `dbDelta()` 不能识别 `CREATE TABLE IF NOT EXISTS` 的确定性迁移错误，并只在 schema/索引/回填验证成功后推进 `wpcomments_db_version`。
- WordPress 5.9.3 × PHP 7.4.33/8.0.30 单站 runtime、邮件、disable/REST/XML-RPC 通过；PHP 7.4 多站点 2 sites 通过。
- PHPStan level 5 逐文件 36/36 exit 0；Plugin Check 发布范围 0 errors / 92 warnings；PHPCS WordPress-Extra 11950 errors / 490 warnings，仍 NOT PASS。
- 发布 ZIP 排除 dev/test/敏感文件；版本头、Stable tag、最低 WordPress/PHP 一致；默认卸载保留通过。最终 ZIP SHA-256 `c5ae7c4f96ab1859ba512bc27f8f2678002d23eb0874e724a6ee2438fcc5ea35`。
- GitHub 仓库没有 Actions workflow，PR head 无实际 CI job；CI 仍是发布阻断。FeiCode 缺仓治理问题保留。

## 2026-08-09 — [CX] 第四轮 CI 与发布门收口

- 同一 PR #1 新增最小 GitHub Actions：lint、PHPCompatibilityWP、PHPStan、WordPress 5.9/PHP 7.4+8.0、WordPress 7.0.2/PHP 8.3 单站+多站、release ZIP；只读权限，不使用生产 secret。
- 第一方警告定向修复后 Plugin Check 发布范围为 0 errors / 76 warnings；PHPCS WordPress-Extra 为 11926 errors / 490 warnings，仍 NOT PASS。没有 vendor/构建产物混入这两个发布范围数字。
- PHPCompatibilityWP 7.4+ PASS；PHPStan level 5 固定依赖、单进程、2 GiB 整组 0 errors PASS。
- WordPress 7.0.2/PHP 8.3.33 最终单站 runtime/email/disable/REST/XML-RPC 与两站 multisite PASS；第三轮 WP5.9.3/PHP7.4/8.0 证据保留。
- 发布包检查明确拒绝 `.github`、tests、docs/HANDOFF、vendor、Composer/npm/静态配置和敏感/临时文件；未发布。
- implementation head `b28da3f` 的 push/PR 两个 Actions runs 共 16/16 check runs success：lint、PHPCompatibility、PHPStan、release ZIP 和四个 WordPress matrix cells 全部 PASS。
- 发布门：代码测试 PASS；静态检查 NOT PASS；自动 CI PASS；FeiCode 真源治理 NOT PASS。PR 保持 Open，禁止合并、发布、部署。最终测试 ZIP SHA-256 `5067d43ea6348edd5f7ba5982b082ae3cbd31322f01ec645a5d133aee8f7f86c`。


## 2026-08-11 — [CX] 第五轮本地候选、增量门与 FeiCode 草案

- 同一 PR #1 保留全部用户功能；展示名改为 WenPai Comments，但包 slug `wpcomments` 仍需身份/升级人审，是 WordPress.org 发布阻断。
- 评论插件字段新增共享上下文 nonce并在插入前验证；验证/字符限制改用 `preprocess_comment` 核心数据；真实 E2E 修复登录态订阅复选框重复渲染。
- 举报 DB v3：验证表标识符、两个复合索引、后台短缓存及写失效、实时 anti-abuse 查询；订阅只保存 `on`，迁移删除旧 `off` meta。
- release PHPCS 覆盖 30 个变更第一方运行 PHP，exit 0；PHPCompatibilityWP、PHPStan、lint、静态契约均 PASS。全仓 WordPress-Extra 12090 errors / 477 warnings 仍 NOT PASS。
- Plugin Check 2.0.0 发布 ZIP 为 0 errors / 1 warning；唯一剩余为 `wpcomments` slug，CSV SHA-256 `433e947dfd7a9750d264642ec93b4978b0d45b693267304653cbde8f68afbfcf`。
- 最终本地矩阵：WP5.9/PHP7.4、WP5.9/PHP8.0、WP7.0.2/PHP8.3 单站及 WP7.0.2/PHP8.3 两站 multisite 全部 exit 0；单站/多站旧表迁移、幂等、失败保护/重试和 Chromium E2E 均 exit 0。
- FeiCode 精确历史导入、保护、单向镜像边界、回滚与 devops Board change 草案在 `docs/governance/wpcomments-feicode-import.md`；草案未执行。
- GitHub CI 与可复现 ZIP 的最终 head 证据待候选提交后补记。PR 继续 Open；禁止合并、发布、部署。
