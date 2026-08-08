# /fonts

Self-hosted variable fonts. Nothing is requested from Google — the theme
inlines the `@font-face` rules and preloads the two latin faces used above the
fold, which removes two third-party connections from every page load.

| File | Family | Axes | Subset |
| --- | --- | --- | --- |
| `inter-latin.woff2` | Inter | `wght 100–900` | latin |
| `inter-latin-ext.woff2` | Inter | `wght 100–900` | latin-ext |
| `cormorant-garamond-latin.woff2` | Cormorant Garamond | `wght 300–700` | latin |
| `cormorant-garamond-latin-ext.woff2` | Cormorant Garamond | `wght 300–700` | latin-ext |
| `pinyon-script-latin.woff2` | Pinyon Script | static, 400 | latin |

Inter is used for interface text and body copy; Cormorant Garamond is the
display face for headings, prices and pull quotes; Pinyon Script sets the
“Treats” wordmark only.

The `unicode-range` on each face means a visitor who only reads English never
downloads the latin-ext files at all — about 119KB saved.

## Licence

Both families are licensed under the **SIL Open Font License 1.1**, which
permits bundling and self-hosting in a commercial website.

- Inter — Rasmus Andersson, <https://rsms.me/inter/>
- Cormorant Garamond — Christian Thalmann / Catharsis Fonts,
  <https://github.com/CatharsisFonts/Cormorant>
- Pinyon Script — Nicole Fally

Full licence text: <https://scripts.sil.org/OFL>

## Replacing them

Swap the `.woff2` files and update the family names in
`inc/enqueue.php` (`treats_font_face_css()`) and the `--font-display` /
`--font-sans` tokens at the top of `css/main.css`. Keep the preloads in
`treats_resource_hints_head()` pointing at whatever loads above the fold.
