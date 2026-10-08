# fixtures/models/ — Development test models

These files are **development fixtures only**. The agent uses them to:

- Test the Three.js viewer before asset upload exists.
- Seed the isolated verification database (`DevFixtureSeeder`).
- Test the bulk upload flow in the browser.

These files are **not** user data. User uploads go to private Laravel storage (`storage/app/private/assets/`). Never copy user uploads into this folder. Never deploy this folder to production.

## Folder layout

Put each model in the folder of the tool that made it:

```text
fixtures/models/
├── blender/
├── prisma3d/
└── spline/
```

Use GLB where possible. A GLTF file must keep its `.bin` and texture files in the same folder.

## File register

Complete one row for each file. The seeder reads this table for metadata. Only use files that you have the right to use.

| File | Object | Tool | Author | Production time (h) | Licence / source |
|---|---|---|---|---|---|
| `blender/apel.glb` | Apple | Blender | Dummy Author A | 1.5 | Dummy metadata (dev fixture) |
| `prisma3d/ayam.glb` | Chicken | Prisma3D | Dummy Author B | 2.0 | Dummy metadata (dev fixture) |
| `spline/buku.glb` | Book | Spline | Dummy Author C | 0.5 | Dummy metadata (dev fixture) |

## Recommended minimum set

- The same object (for example, Bird) from all three tools — for the Side-by-Side Test and the Tool summary.
- The same two or three objects from each tool — for the Crowd Test Per tool.
- One small file (< 1 MB) and one large file (> 10 MB) — for load and limit tests.
- One file with textures and one without — for texture-memory metrics.
- One invalid file (for example, a renamed `.txt`) — for upload validation tests.
