# CLAUDE.md

Guidance for Claude Code when working in this repository.

## Project

Website for **Treats Tea Room Café**. The repo is at a very early stage — it
currently holds only a placeholder README and the logo asset. No site code,
build tooling, or framework has been committed yet.

Repo: `treatstearoomcafe0129/TreatsWebsite`

## Current contents

| File | Notes |
| --- | --- |
| `README.md` | Placeholder — title only. |
| `treats-tea-room-luxury-logo-1.svg` | Café logo. **Not a true vector**: it is a single `<image>` element wrapping a base64-encoded PNG, ~2.1 MB. |

### Working with the logo

- Because it is a wrapped bitmap, it will not scale crisply beyond its native
  1522×993 and cannot be recoloured by editing SVG paths.
- Do not inline this file into HTML/CSS — 2.1 MB in the markup will hurt page
  load. Reference it as a separate asset, and prefer a resized/compressed
  export (WebP or PNG) for actual page use.
- If a genuine vector version becomes available, replace this file rather than
  adding a second copy.

## Tech stack

Not yet chosen. Before scaffolding anything, ask which direction is wanted
rather than assuming. Once decided, record the choice here along with the
install/run/build/deploy commands.

- Framework / static: TBD
- Hosting: TBD
- Package manager: TBD

## Commands

None yet — there is no `package.json` or build config. Add the real commands to
this section as soon as tooling lands, e.g.:

```
# install   TBD
# dev       TBD
# build     TBD
# deploy    TBD
```

## Content guidance

Anything user-facing (hours, menu, prices, address, phone, booking details) is
real business information. Never invent it — if a value is unknown, ask, or
leave a clearly marked `TODO` placeholder in the markup. Do not ship
lorem-ipsum-style filler that reads as real café detail.

## Conventions

- Keep the site simple and easy for a non-developer to edit.
- Large binary/media assets belong in a dedicated assets directory once the site
  structure exists; keep the repo root uncluttered.
- Accessibility basics are expected on any markup added: alt text on images,
  sensible heading order, sufficient colour contrast.
- Site must work well on mobile — most café visitors will arrive on a phone.

## Git workflow

- Default branch: `main`.
- Work on feature branches; do not commit directly to `main` without being
  asked.
- Do not push to a branch other than the one assigned for the task.
- Do not open a pull request unless explicitly requested.
