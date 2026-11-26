# Remove authored code comments

## Objective and scope

Remove actual comments from PASOS-owned code, tests, SQL and example configuration, including // comments. Preserve URLs, strings, regexes, fixture contents, whitespace boundaries and executable behavior. Exclude dependencies, build output, Markdown, other projects and private configuration source files.

## Tasks and route

- [x] M1 — Remove the 35 parser-identified comments in 17 files and update both existing delivery archives without changing their configuration or identity variants. Delegated direct: token-aware multi-file transformation and archive handling.
- [x] M2 — Independently verify zero actual comments, noncomment token parity, archive variant preservation and existing regressions/build. Delegated verifier plus parent spot check.

## Constraints and checks

Use installed PHP/JavaScript/SQL parsers or quote-aware scanners; never blanket-replace //. Preserve the packaged trusted lawyer UID independently of repository SQL, and preserve private OAuth archive entry bytes. Repository ZIP must remain credential-free. Keep owner-only backups of archives outside Git/web roots. No dependency, behavior, database, account, deployment or authenticated service changes.

This is a mechanical comment-only change: no meaningful behavioral RED is applicable. Observe the existing nonzero inventory, then prove zero comments and unchanged noncomment token streams. Run PHP lint, four PHP regressions, frontend SFC regression, frontend build and git diff --check. Native risk assessment remains provider-owned; do not claim approval from comment parity alone.

## Delivery and progress

Strategy single-pr, one coherent cleanup with local work-unit commits; no push requested. Forecast approximately 35 removed comments, two regenerated binary artifacts and this tracking document, not application growth.

- Baseline `6b7e1e3` source and public ZIP snapshots matched Git exactly. Initial inventory: 25 PHP, 4 JavaScript/MJS and 6 SQL comments in 17 files, all single-line; no executable directives identified.
- M1 source result: 35 -> 0 comments across all 40 tracked authored PHP, JS/MJS, Vue, HTML, CSS and SQL files. Private source configuration, dependencies and generated output were excluded. No application behavior or configuration values changed.
- Token proof: PHP `token_get_all` noncomment/nonwhitespace token types and text match; JavaScript Babel ASTs match after excluding positional/comment metadata, and raw tokens plus between-token line-break flags match; quote/backtick/escape/doubled-quote-aware MySQL scanner noncomment tokens and literal bytes match. Template/compiler-dom, SFC and PostCSS scans found zero actual comments. Temporary parser/scanner self-checks passed. All internal newline boundaries were retained; only comment-adjacent trailing spaces and newly trailing EOF blank lines were removed.
- Both ZIPs were transformed independently from their original payloads: 35 -> 0 comments in each, with exactly 17 changed entries each. Public ZIP: 44 entries, 87,728 -> 86,159 bytes, mode 0644. Private ZIP: 45 entries, 94,196 -> 92,627 bytes, mode 0600. Entry order/names, ZIP comments, useful entry metadata and flags, duplicate-free manifests and CRCs passed; every other entry payload is byte-identical, including archive-specific README content. Each archive's SQL literal/UID binding is unchanged, not replaced with repository SQL.
- Private archive OAuth configuration entry is byte-identical and has zero actual comments; private source configuration was not edited. The public archive has no private OAuth configuration entry or detected private-key/service-account/JSON refresh-token/client-secret patterns. No credentials or UID values were printed or recorded.
- Backups and proof: `/tmp/opencode/pasos-comment-cleanup-RdMCW6sY/` is mode 0700; `public-original.zip` and `private-original.zip` are mode 0600, outside Git and web roots. `proof.json`, `source-originals/` and temporary PHP/Babel/SQL tooling are retained there for the independent verifier. No permanent tool or dependency was added.
- Verification passed after final whitespace cleanup: PHP lint for all 12 changed PHP/example files; foreground request-guard, consultation-rules, google-calendar-meet and firebase-auth PHP regressions with assertions enabled; `node --experimental-vm-modules tests/frontend-sfc.mjs`; `npm --prefix frontend run build`; `git diff --check`. Expected warnings: Node experimental VM modules and Vite's existing >500 kB bundle warning. No tests were weakened or behavior adjusted.
- Limits: no live MySQL concurrency test, migrations, Google/browser authentication, deployment, accounts, authenticated application APIs or services were run. No commits, staging, push or PR occurred. M2 remains pending independent verification and parent spot check; M1 evidence alone is not independent approval.
- Skill resolution used the explicitly injected `skills/interfaces-programacion-web/SKILL.md` and `/home/lpalavecino/.cache/opencode/npm/@dietrichgebert/ponytail@latest/1793617125446/node_modules/@dietrichgebert/ponytail/skills/ponytail/SKILL.md`, plus the course source index. Engram task mirror: observation 6406, topic `odd/remove-code-comments/tasks`, project `agente-interfaces`.
- Independent verification confirmed 35 -> 0 actual comments in 40 authored files and both archives, unchanged executable tokens/ASTs/literals and unchanged private archive OAuth bytes. All 12 PHP lints, four PHP regressions, SFC regression, frontend build and diff check passed independently. No attributable defect was found. Historical ignored private-source immutability could not be reconstructed independently because its baseline digest was not retained; that source file was outside all authorized write surfaces.
- Parent spot check independently compared FirebaseAuth.php noncomment tokens against 6b7e1e3, confirmed zero actual comments in that helper, reran the request-guard regression and git diff check successfully. Native assessment and local work-unit commit evidence remain pending.
- Native assessment classified the normalized candidate high risk because it includes tests/firebase-auth.php (19 paths, 105 changed lines). User explicitly selected Skip this time; exact provider decline returned action declined and consent declined_this_candidate. No native review record or approval is claimed; future reviews remain enabled.

## Next step

Assess the final candidate, record the local work-unit commit and hand off the comment-free source/archives. Do not claim deployment or live authentication tests, and do not push without user authorization.
