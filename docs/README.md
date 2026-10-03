# docs/ — Index

This folder contains the specification for the 3D Render Test Dashboard. Each file has one area of authority. When two files disagree, the file with the authority for that area is correct. If the conflict is not clear, stop and ask the user.

| File            | Authority                                                                     | Changes when                           |
| --------------- | ----------------------------------------------------------------------------- | -------------------------------------- |
| `PRD.md`        | What the product does: scope, roles, features, data model                     | Product behavior changes               |
| `BENCHMARK.md`  | How the product measures: metrics, test methods, budgets, device classes      | Methodology changes                    |
| `DESIGN.md`     | How the product looks: tokens, typography, components, icons, motion          | Visual system changes                  |
| `SITEMAP.md`    | Where things are: routes, shells, navigation, public/private boundary         | Information architecture changes       |
| `AGENT.md`      | How the agent works: workflow, decision gates, stack rules, data safety       | Workflow or tooling changes            |
| `PROJECT.md`    | When things are built: milestones, scope, acceptance criteria, later versions | Milestone order or scope changes       |
| `DEPLOYMENT.md` | Where it runs: hosting requirements, build and deploy procedure               | Hosting or build changes               |
| `LOG.md`        | What was done: history, decisions, blockers, status                           | After each milestone and each decision |

## Rules for these documents

- Write a rule in one file only. Other files refer to it.
- Record each decision in `LOG.md`, then update the file with the authority.
- Do not add "already implemented" notes to the specification files. Status goes in `LOG.md` only.

## Project folders outside `docs/`

| Folder             | Content                                                                             |
| ------------------ | ----------------------------------------------------------------------------------- |
| `CLAUDE.md` (root) | Agent entry point. It imports `docs/AGENT.md`.                                      |
| `fixtures/models/` | Development test models (GLB/GLTF). Not user data. See `fixtures/models/README.md`. |
