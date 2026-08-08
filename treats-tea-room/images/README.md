# /images

Theme-owned graphics. Everything here is generated for the theme — photography
belongs in the WordPress media library, not in this folder.

| File | Used for |
| --- | --- |
| `favicon.svg` | Browser tab icon (used when no Site Icon is set in the Customizer) |
| `apple-touch-icon.png` | iOS home-screen icon, 180×180 |
| `icon-192.png`, `icon-512.png` | Web app manifest icons |
| `social-default.jpg` | Fallback Open Graph / Twitter card image, 1200×630 |
| `logo.svg` | Optional. The site logotype — drop it here and the theme uses it |
| `texture-suede.png` | The suede nap tiled across the sage ground, 320×320, seamless |
| `texture-suede-cloud.png` | Slow, large-scale variation in the pile, 512×512, seamless |

## The suede texture

`texture-suede.png` is a greyscale height-lit fibre field, generated on a
torus so it tiles with no seam and — unlike a mirrored tile — no symmetry to
read as a pattern. It is blended over the sage with `soft-light`, so it
carries light and shade rather than colour; its mean is exactly mid-grey, so
it neither lightens nor darkens the ground overall.

Regenerating it (numpy + Pillow): blur white noise with a wrap-around
Gaussian, take periodic gradients with `np.roll`, light them from azimuth
128° / elevation 55°, re-centre on 0.5, then quantise. Keep the mean at 127
or the whole palette shifts and the contrast tuning stops holding.

## The logo

`logo.svg` is not shipped — the theme falls back to its built-in wordmark
without it. Drop the real artwork in as `logo.svg` (or `logo.png`, or
`logo.webp`) and it appears in the header and the footer, no dashboard visit
needed. A logo uploaded through *Customize → Site Identity* always wins over
this one.

It is rendered at 52px tall, capped at 240px or 46vw wide, whichever is
smaller, so an unusually wide logotype cannot push the header off a phone.
Width and height attributes are read from the file — from `width`/`height` or
the `viewBox` on an SVG — so it reserves its space before it loads.

## Replacing them

Set a **Site Icon** under *Customize → Site Identity* and WordPress will
generate every favicon size for you; the files above are only the fallback.

For sharing images, set *Customize → Treats Tea Room → SEO & Sharing → Default
sharing image* to a real photograph — a 1200×630 crop of the tea room or the
cake counter will always outperform a generated card.

## Photography guidance

The layout is built for warm, bright, natural-light photography with plenty of
negative space:

- **Hero:** landscape, at least 2400px wide, with quiet space on the left third
  where the headline sits.
- **Menu category cards:** portrait or square, 900×1200 or 900×900.
- **Menu items:** square, 800×800, shot from above on a light surface.
- **Gallery:** any ratio; the grid crops to squares and one wide tile per row.

Upload at full resolution and let WordPress generate the sizes — the theme
registers `treats-hero`, `treats-card`, `treats-card-tall`, `treats-square` and
`treats-thumb` crops and serves them responsively.
