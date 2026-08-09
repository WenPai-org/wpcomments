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
