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
