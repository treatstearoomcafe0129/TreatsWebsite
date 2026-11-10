# Treats afternoon tea film

`treats-afternoon-tea.mp4`: 34 s, 1920×1080, 24 fps, H.264 + AAC stereo.

This is the second version. It is set in the brand's sage palette, with type in Instrument Serif and Inter Tight, and shows a 3D-rendered afternoon tea on a sage linen cloth. Everything is generated in code. There is no stock footage or music.

| File | What it is |
| --- | --- |
| `film2.html` | The film. It has sage-suede title and end cards (the theme's own textures and logo), five camera shots, and frosted captions like the website's. |
| `tea3d.js` | The three.js scene, built and lit procedurally: a porcelain three-tier stand with gold trim, finger sandwiches, scones with jam and clotted cream, macarons, éclairs, strawberry tarts, lemon drizzle, a teapot and cups. |
| `score.py` | Synthesises the ambient score, with notes timed to the cuts. |
| `render2.js` | Renders the frames deterministically with Playwright (headless Chromium). |
| `fonts/` | Instrument Serif and Inter Tight (SIL Open Font License), self-hosted. |
| `film.html`, `render.js`, `treats-brand-film.mp4` | The first version (dark and gold). |

## Swapping in real photography

The 2021 photoshoot (Treats-1533 to Treats-1668.jpg) is in Google Drive. To use it, put the photos in `video/photos/`. Any 3D shot in `film2.html` can then be replaced by a slow pan-and-zoom on a photo.

## Rebuilding

```bash
npm install                      # three.js
python3 -m http.server 8765 &    # from the repo root
cd video && python3 score.py
node render2.js frames 24 0 816 frames/
ffmpeg -framerate 24 -i frames/f%05d.jpg -i score.wav -c:v libx264 -crf 17 -pix_fmt yuv420p -c:a aac -b:a 256k -shortest treats-afternoon-tea.mp4
```
