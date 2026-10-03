# CLAUDE.md — 3D Render Test Dashboard

This file is the entry point for the coding agent. The full rules are in `docs/`.

@docs/AGENT.md

## Reading order

Before any work, read these files in this sequence:

1. `docs/README.md` — index and the authority of each file.
2. `docs/PROJECT.md` — find the current milestone.
3. `docs/LOG.md` — find the current status and the decisions already made.
4. `graphify-out/GRAPH_REPORT.md` — codebase map (when it exists; see "Graphify" below).
5. `docs/PRD.md` — product scope.
6. `docs/BENCHMARK.md` — benchmark methodology (read before any Performance Lab work).
7. `docs/DESIGN.md` — visual system (read before any UI work).
8. `docs/SITEMAP.md` — routes and page boundaries (read before any route work).
9. `docs/DEPLOYMENT.md` — hosting limits (read before you add a dependency or a server process).

## Hard rules (summary)

- Do one milestone at a time. Ask for approval before you start it. Stop when it is complete.
- Ask before any product, UX, data-model, methodology, or visual-system decision.
- Never wipe, truncate, or overwrite the local working database or uploaded files. Use the isolated verification database for tests.
- `fixtures/models/` contains development test models only. Never treat them as user data. Never copy user uploads into `fixtures/`.
- Update `docs/LOG.md` at each milestone boundary.

---

## Caveman (always on)

Use the **caveman** skill for every session: https://github.com/JuliusBrussee/caveman/blob/main/skills/caveman/SKILL.md

Install (once, global):

```bash
npx skills add JuliusBrussee/caveman -g
```

Rules:

- Activate at the start of every session at intensity **full** (`/caveman`). Do not wait for the user to ask.
- Stay in caveman mode for the whole session. Leave it only when the user says "stop caveman" or "normal mode".
- Use caveman only for chat messages to the user (progress updates, summaries, explanations).

Never compress these. Write them in full, normal form:

- Code, code blocks, commands, file paths, config keys, API names, technical terms.
- Exact error messages (quote them verbatim).
- Security warnings.
- Confirmations of irreversible or destructive actions (database wipe, file deletion, permanent delete, deployment).
- Decision questions under `docs/AGENT.md` §3. State the decision, the options, and the consequences in clear full sentences. Use caveman **lite** at most.
- Multi-step logic where a missing word could change the meaning.

Files are never written in caveman style. Write normal prose in `docs/*.md` (including `LOG.md`), code comments, commit messages, `README.md`, and report content.

Clarity wins over brevity. If caveman wording could cause a wrong action, use normal wording.

If the skill is not installed in the environment, tell the user once, give the install command, and continue with short, plain wording. Do not write a project-specific imitation of the skill.

---

## Graphify (always on)

Use **Graphify** (https://graphify.net/) to keep a knowledge graph of the codebase and docs. Read the graph before you search raw files.

Install (once, on the developer machine; needs Python):

```bash
pip install graphifyy
graphify install
```

Project setup (once, in the project root, during M0.1 after Laravel is installed):

```bash
graphify claude install     # adds the Graphify directive to this CLAUDE.md and a PreToolUse hook
graphify hook install       # git post-commit / post-checkout hooks rebuild the graph
```

Then build the first graph with `/graphify .` and check `graphify-out/GRAPH_REPORT.md`.

Rules:

- Before a milestone, and before architecture questions, read `graphify-out/GRAPH_REPORT.md`. Use `/graphify query`, `/graphify path`, and `/graphify explain` before broad `Glob`/`Grep` sweeps.
- Rebuild the graph at the end of each milestone (after the `LOG.md` update) if the git hook did not already run.
- Exclude these from the graph: `vendor/`, `node_modules/`, `public/build/`, `storage/`, `bootstrap/cache/`, `fixtures/models/` (binary models), `graphify-out/`. Use Graphify's ignore mechanism; confirm the exact file name and syntax for the installed version at M0.1.
- `graphify-out/` is a generated folder. Add it to `.gitignore`. Never deploy it.
- `graphify claude install` writes into this file. Keep its section. If it changes or removes any other part of this file, restore that part and tell the user.
- The graph is a map, not the authority. When the graph and the code or `docs/` disagree, the code and `docs/` are correct; rebuild the graph.
- If Graphify is not installed, tell the user once, give the install commands, and continue without it.

## graphify

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

Rules:
- For codebase questions, first run `graphify query "<question>"` when graphify-out/graph.json exists. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- After modifying code, run `graphify update .` to keep the graph current (AST-only, no API cost).
