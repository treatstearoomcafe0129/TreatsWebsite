# /images

Theme-owned graphics. Everything here is generated for the theme — photography
belongs in the WordPress media library, not in this folder.

| File | Used for |
| --- | --- |
| `favicon.svg` | Browser tab icon (used when no Site Icon is set in the Customizer) |
| `apple-touch-icon.png` | iOS home-screen icon, 180×180 |
| `icon-192.png`, `icon-512.png` | Web app manifest icons |
| `social-default.jpg` | Fallback Open Graph / Twitter card image, 1200×630 |
| `logo.svg` | The Treats logotype, vector. This is the one the theme serves |
| `logo.png` | The same logotype as the original raster artwork, 720×380. Kept as the faithful master; not served while `logo.svg` exists |
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

`logo.svg` is what the theme serves. `logo.png` is the same mark as a raster,
kept because it is the faithful original; the lookup order is `logo.svg`,
`logo.png`, `logo.webp`, so deleting the SVG falls back to it. A logo uploaded
through *Customize → Site Identity* beats both.

### Where they came from

The artwork arrived as a 2.1MB "SVG" that was really a 1522×993 PNG base64'd
inside an `<image>` wrapper, with a wide transparent margin around the mark.

`logo.png` is that image trimmed to its opaque bounds, resized to 720px wide
and quantised to a 256-colour palette — 59KB, a 27× saving, with no banding in
the gold at 2× zoom. Use `Image.quantize(colors=256, method=Image.FASTOCTREE)`
to regenerate: that method keeps the alpha channel, the default does not.

`logo.svg` is a genuine vector, traced from the same raster with vtracer and
then coloured by hand. **42KB, about 16KB over the wire once gzipped**, and
resolution-independent. It was built in three passes:

1. Trace the alpha mask as a binary silhouette (`colormode='binary'`,
   `path_precision=1`). Colour tracing was tried first and produced 531KB to
   1.7MB — far worse than the raster, and blotchy.
2. Split the shapes by connected component: the two script strokes take the
   metallic gradient, the eighteen small glyphs of "Est.1984" and
   "TEA ROOMS | CAFE" take a flatter, darker one. Without that split the fine
   caps line landed in the pale end of the gradient and vanished at header
   size.
3. Sample the gold from the original for the gradient stops.

What it trades away is the 3D bevel modelling inside each stroke — the vector
is elegant flat gold where the raster is embossed metal. At 56–68px, the size
it actually renders at, side-by-side comparison on both the sage and the dark
ground showed the vector reading *better*, because nothing is being resampled.
At poster sizes the raster is richer. If a true vector master ever turns up
from whoever drew it, prefer it over both.

### How it is sized

The logo renders at `--t-header-h` minus 32px — 56px on phones, 68px from
640px up — and is capped at 240px or 46vw wide, whichever is smaller, so an
unusually wide logotype cannot push the header off a phone. Width and height
come from the file, so it reserves its space before it loads.

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
