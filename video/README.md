# Treats brand film

`treats-brand-film.mp4`: 34 s, 1920×1080, 30 fps, H.264 + AAC stereo.

Everything is generated in code. There is no stock footage or music.

- `film.html`: the film itself. It draws a WebGL silk-light background, canvas bokeh, gold dust and particles, SVG gold line art and type set in the theme's own fonts and logo. Open it through a local server to watch it loop live.
- `score.py`: synthesises the ambient score (a pad, music-box notes timed to the scenes and a shimmer under the logo reveal) into `score.wav`.
- `render.js`: renders each frame deterministically with Playwright and pipes the frames to ffmpeg.

Rebuild from the repo root:

```bash
python3 -m http.server 8765 &
cd video && python3 score.py && node render.js video silent.mp4 30
ffmpeg -i silent.mp4 -i score.wav -map 0:v -map 1:a -c:v copy -c:a aac -b:a 256k -shortest treats-brand-film.mp4
```
