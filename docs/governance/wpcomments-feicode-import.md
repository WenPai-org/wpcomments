# WPComments GitHub → FeiCode 真源导入方案（草案，未执行）

> [CX] 本文只定义待 devops 人审的仓库创建与精确 Git 历史导入。它不授权合并 PR、创建 tag/release、发布、部署、删除/覆盖已有仓库或启用持续镜像。

## 1. 目标与边界

- 冻结源：`https://github.com/WenPai-org/wpcomments.git`，执行时记录 `main` 与 `codex/wpcomments-audit-hardening` 的最终 OID。
- 目标：创建空且未初始化的 FeiCode `WenPai-org/wpcomments`；建议保持 GitHub 当前公开可见性，`mirror=false`，不预建 README/LICENSE/.gitignore。
- 导入全部 `refs/heads/*` 与 `refs/tags/*`，逐 OID 保留历史；GitHub `refs/pull/*` 只进入签名/校验归档证据，不写入 Forgejo 保留命名空间。
- GitHub PR #1 的评论、review、checks 等非 Git 对象继续保留在 GitHub。
- 若目标仓已存在或非空，立即停止；不 force push、不删除、不覆盖。

## 2. 冻结与证据

以下命令是人工变更窗口草案，**本次不得执行**。`DST` 必须由新建空仓页面取得；日志不得记录凭据。

```bash
set -euo pipefail
SRC=https://github.com/WenPai-org/wpcomments.git
DST=<FEICODE_PUSH_URL_FROM_NEW_EMPTY_REPO>
ROOT=$(mktemp -d)

git clone --mirror "$SRC" "$ROOT/source.git"
git -C "$ROOT/source.git" fsck --full
git -C "$ROOT/source.git" for-each-ref \
  --format='%(objectname) %(refname)' refs/heads refs/tags \
  | LC_ALL=C sort > "$ROOT/source.refs"
git -C "$ROOT/source.git" rev-list --objects --branches --tags \
  | LC_ALL=C sort | sha256sum > "$ROOT/source-objects.sha256"
git -C "$ROOT/source.git" bundle create \
  "$ROOT/wpcomments-github-pre-feicode.bundle" --all
sha256sum "$ROOT/wpcomments-github-pre-feicode.bundle" \
  > "$ROOT/wpcomments-github-pre-feicode.bundle.sha256"

test -z "$(git ls-remote --heads --tags "$DST")" || {
  echo 'destination is not empty; stop' >&2
  exit 3
}
```

归档 `source.refs`、reachable-object SHA、bundle SHA、冻结 OID、GitHub PR #1 URL/state/check evidence 到受保护运维存储。验收记录只写摘要，不写 token、密码或带凭据 URL。

## 3. 精确导入与验证

```bash
git -C "$ROOT/source.git" remote add feicode "$DST"
git -C "$ROOT/source.git" push feicode \
  'refs/heads/*:refs/heads/*' \
  'refs/tags/*:refs/tags/*'

git clone --mirror "$DST" "$ROOT/destination.git"
git -C "$ROOT/destination.git" fsck --full
git -C "$ROOT/destination.git" for-each-ref \
  --format='%(objectname) %(refname)' refs/heads refs/tags \
  | LC_ALL=C sort > "$ROOT/destination.refs"
git -C "$ROOT/destination.git" rev-list --objects --branches --tags \
  | LC_ALL=C sort | sha256sum > "$ROOT/destination-objects.sha256"

diff -u "$ROOT/source.refs" "$ROOT/destination.refs"
diff -u "$ROOT/source-objects.sha256" "$ROOT/destination-objects.sha256"
```

只有双方 `fsck` exit 0、refs diff exit 0、reachable-object diff exit 0 才是 PASS。空 tag 集合本身不能代替对象和 refs 验证。

## 4. 默认分支、保护与镜像关系

1. refs 验证完成后把 FeiCode 默认分支设为 `main`。
2. 对新仓先做单仓 dry-run，再按现有 W1 最小规则保护 `main`：push whitelist 开启且 users/teams/deploy keys 均为空，不允许直接推送。第一阶段 approvals=0；真实 FeiCode CI 尚无样本前不虚构 required status checks。
3. FeiCode 经人审切换后成为唯一 canonical source。GitHub 保持下游 review/distribution archive。
4. 持续镜像不属于本次 Board change。未来另行审批时只能启用 FeiCode → GitHub 的单向 heads/tags push mirror；禁止 GitHub → FeiCode pull mirror和双向循环。
5. GitHub PR #1 保持 Open、unmerged，可作为历史 review 证据；不得把它的 synthetic merge ref 当产品历史。

现有 `scripts/cluster/feicode-protect-main.sh` 的 409 分支会删除后重建保护规则；因此新仓只允许先 dry-run/read-back，若意外发现既有规则必须停止，不走破坏性重试。

## 5. 回滚

- canonical 切换前失败：停止导入，GitHub 仍是 canonical；不删除任何仓库。
- 导入校验不一致：停止写入；经人工批准后把新仓归档为 `import-failed-<timestamp>`，保留 bundle/manifests，另开变更创建新的空目标。禁止 force-rewrite 或删除历史。
- canonical 切换后发现问题：先停用 FeiCode 出站镜像，把 FeiCode 设只读/归档，恢复 GitHub canonical 标记；不合并、发 tag/release、部署或重写分支。bundle 与 manifests 用于重建。

## 6. 验收记录

- 目标 URL、visibility、`mirror=false`、default=`main`。
- source/destination refs manifests、reachable-object SHA、bundle SHA。
- 双方 `fsck` 与两次 diff 的命令、exit code。
- 冻结 `main` OID 与最终 candidate OID。
- main protection 只读回读结果（脱敏）；持续镜像未启用。
- PR #1 仍 Open/unmerged；无新 tag/release/deployment。

## 7. devops Board change 草案（不得执行）

`change` 任务必须由 devops 创建并人工审批；`development_release` 只用于路由 WenPai 执行。该草案只申请建仓、导入、验证和保护，不申请合并或发布。

```bash
PID=P-wpcomments-feicode-sot-<YYYYMMDD>
board-publish project \
  --id "$PID" \
  --title "建立 WPComments FeiCode 真源" \
  --owner devops \
  --stakeholders "wenpai,linuxjoy" \
  --links '{"github":"https://github.com/WenPai-org/wpcomments","github_pr":"https://github.com/WenPai-org/wpcomments/pull/1","audit":"/home/parallels/Projects/wpcomments-codex-audit/docs/audits/wpcomments-audit-2026-08-09.md"}'

board-publish task \
  --project "$PID" \
  --title "创建并导入 WenPai-org/wpcomments FeiCode 真源" \
  --capability development_release \
  --family change \
  --priority high \
  --agent any \
  --created-by devops \
  --task-text "仅创建空的 FeiCode WenPai-org/wpcomments（推荐 public，mirror=false，不初始化文件），从 GitHub 冻结快照导入全部 refs/heads/* 和 refs/tags/*，验证 fsck、ref manifest、reachable object manifest，设置默认分支 main 并按现有 W1 最小规则禁直推。保留 GitHub PR #1 与 refs/pull 证据，不向 Forgejo reserved refs/pull 写入。禁止合并 PR、创建 tag/release、发布、部署、删除/覆盖已有仓库、启用双向镜像或输出凭据。若目标仓已存在或非空立即停止并回报。" \
  --deliverables "feicode_repo_url,visibility,source_refs,destination_refs,bundle_sha256,fsck_and_diff_exit_codes,branch_protection_readback,rollback_record" \
  --acceptance "devops 人审：目标仓 mirror=false、default=main；source/destination heads+tags ref manifests 完全一致；reachable object manifests 完全一致；双方 fsck exit 0；main 保护已只读回读；PR #1 仍 open/unmerged；未创建 tag/release、未发布部署、未启用持续镜像。任一 ref/object/权限差异为 NOT PASS。"
```

## 8. 依据

- Task schema / change 人审 / devops 权限：`/Users/feibisi-studio/Projects/linuxjoy/docs/architecture/task-board-spec.md:60-99`、`:122-135`、`:204-211`。
- Board CLI：`/Users/feibisi-studio/Projects/linuxjoy/scripts/cluster/board-publish:1-15`。
- first-party 非 mirror 与保护/回滚规则：`/Users/feibisi-studio/Projects/linuxjoy/docs/ops/feicode-pr-rollout-devops.md:9-15`、`:31-58`、`:121-130`。
- 当前保护 payload：`/Users/feibisi-studio/Projects/linuxjoy/scripts/cluster/feicode-protect-main.sh:20-36`。
