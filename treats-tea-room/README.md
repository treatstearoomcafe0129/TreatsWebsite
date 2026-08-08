# Treats Tea Room — WordPress theme

A bespoke, production-ready theme for **Treats Tea Room Café**, 10/11 Silver
Street, Durham. Built from scratch: no page builder, no framework, no parent
theme, and no dependency on the previous Enfold installation.

- Frosted-glass panels on a textured sage ground, gold line detailing,
  script wordmark and small-caps serif headings
- Mobile-first, dark-mode aware, WCAG 2.2 AA oriented
- Self-hosted variable fonts, conditional CSS/JS, no jQuery on the front end
- Online booking, Click & Collect, gift voucher orders, contact and newsletter
  forms — all built in, all working without JavaScript
- JSON-LD structured data for the business, menus, FAQs, reviews and breadcrumbs

---

## Installing

1. Copy the `treats-tea-room` folder into `wp-content/themes/`.
2. In the dashboard, go to **Appearance → Themes** and activate **Treats Tea
   Room**.
3. On activation the theme builds the whole site once:
   - every page in the structure below, with its template already assigned;
   - the primary, footer and legal navigation menus;
   - menu categories, a starter menu, FAQs and reviews;
   - Reading settings pointed at the new Home and Journal pages;
   - pretty permalinks, if they were not already on.

   None of this runs twice, and everything it creates is ordinary content you
   can edit or delete.
4. Open **Appearance → Customize → Treats Tea Room** and fill in the real phone
   number, opening hours, social links and photography.

To zip it for a host that only accepts uploads:

```bash
cd wp-content/themes
zip -r treats-tea-room.zip treats-tea-room -x "*.DS_Store"
```

## Pages the theme creates

| Page | Template |
| --- | --- |
| Home | `front-page.php` |
| Breakfast & Brunch | Menu |
| Lunch | Menu |
| Afternoon Tea | Menu |
| Cakes & Desserts | Menu |
| Drinks | Menu |
| Book a Table | Book a Table |
| Gift Vouchers | Gift Vouchers |
| About Us | About |
| Contact | Contact |
| FAQ | FAQ |
| Gallery | Gallery |
| Journal | default (blog index) |
| Privacy Policy, Accessibility | default |

The home page is assembled from `template-parts/home/` in the order set by the
`treats_home_sections` filter — by default `hero`, `values`, `actions`,
`contact-bar`, matching the approved design. The menu-preview, reviews and
gallery sections are still in the folder and are one line away if you want
them back:

```php
add_filter( 'treats_home_sections', function ( $s ) {
	array_splice( $s, 3, 0, array( 'menus', 'reviews' ) );
	return $s;
} );
```

Each **Menu** page is bound to a menu category in the *Treats details* panel on
its edit screen. Change that dropdown and the page shows a different menu — no
code involved.

## Where the content lives

| What | Where in the dashboard |
| --- | --- |
| Dishes, prices, dietary labels | **Menu** (custom post type) |
| Menu sections and sub-sections | **Menu → Categories** |
| Vegan / GF / nut labels | **Menu → Dietary** |
| Customer reviews | **Reviews** |
| FAQ questions and topics | **FAQs** |
| Bookings received | **Bookings** |
| Click & Collect orders | **Collection Orders** |
| Gift voucher orders | **Voucher Orders** |
| Contact form messages | **Enquiries** |
| Newsletter sign-ups | **Subscribers** |

Booking, order, voucher, enquiry and subscriber records are private: they never
appear on the front end, are excluded from search, and cannot be created from
the dashboard — only the front-end forms write to them.

## Customizer settings

Everything business-specific is under **Customize → Treats Tea Room**:

- **Brand & Appearance** — business name, wordmark script word and caps line,
  accent colour,
  default colour scheme, dark-mode toggle, scroll animations
- **Contact & Location** — address, phone, public email, notification email,
  Google Maps embed, travel note
- **Opening Hours** — per-day open/close/closed, plus a note
- **Social Profiles** — Facebook, Instagram, Tripadvisor, X
- **Home Page** — hero copy, hero image, buttons, story section
- **Table Booking** — external booking URL or embed, party size, lead time,
  how far ahead, bookable times, form note
- **Click & Collect** — on/off and the collection notice
- **Gift Vouchers** — intro, amounts, terms, optional payment link
- **Instagram Feed** — handle, access token, post count
- **Newsletter** — heading, text, optional Mailchimp/Brevo form action
- **SEO & Sharing** — meta description, sharing image, price range, year
  established

## Integrations

| Feature | Default behaviour | Optional upgrade |
| --- | --- | --- |
| Booking | Built-in request form → stored + emailed | Set an external URL or paste an OpenTable/ResDiary embed |
| Gift vouchers | Order form → stored + emailed | Add a Stripe/SumUp/Square payment link; buyers are redirected after submitting |
| Newsletter | Subscribers stored in WordPress | Point the form at a Mailchimp or Brevo action URL |
| Instagram | Curated gallery images | Add a Basic Display access token for the live feed (cached hourly) |
| Maps | Click-to-load Google embed | Paste your own embed URL |

## The design system

The visual language lives in one place: the token block at the top of
`css/main.css`, and the "Glass surface system" section at the bottom of it.

| Token group | What it controls |
| --- | --- |
| `--c-bg`, the `body` background layers | The sage ground: light sweep, plaster mottling and a fine SVG grain |
| `--glass-*` | Panel fill, hairline border, blur radius and the inner highlight |
| `--gold` | Gold used for **text**. Tuned to clear 4.5:1 on the glass |
| `--gold-mid`, `--gold-bright` | Gold used for **decoration** — icons, rules, borders — where contrast minimums don't apply |
| `--font-script` | The wordmark face (Pinyon Script) |

Both golds flip with the colour scheme, so components never hard-code one.
If you change the ground colour, re-check the gold: `--gold` has to stay
readable against the darkest part of the mottling, not just against glass.

Reusable pieces: `.glass` (panel), `.glass--pad`, `.icon-badge` (round gold
icon), `.glass-title` (small-caps serif), `.gold-rule` (the short gold line),
`.ornament` (the leaf-between-rules divider).

## Performance

- Fonts are self-hosted (`Cormorant Garamond` + `Inter`, variable, and
  `Pinyon Script` for the wordmark; latin + latin-ext subsets only) and
  preloaded; `@font-face` is inlined.
- Critical CSS for the first paint is inlined; the rest loads normally.
- `css/menu.css` and `css/forms.css` load only where they are needed.
- All scripts are deferred; there is no jQuery on the front end.
- Core block CSS, emoji scripts, oEmbed discovery and jQuery Migrate are
  removed (each is filterable if a plugin needs them).
- The Google Maps iframe is not requested until the visitor clicks the map.
- The Instagram API response is cached for an hour; failures are cached for ten
  minutes so a bad token can never slow the site.

## Accessibility

- Skip link, landmark regions, and a visible focus ring on every interactive
  element.
- The mobile drawer traps focus, closes on <kbd>Esc</kbd>, and is `inert` when
  shut. So is the lightbox.
- Submenus are real `aria-expanded` disclosure buttons, so they work by
  keyboard, not just hover.
- `prefers-reduced-motion` disables every animation, the hero pan and smooth
  scrolling.
- `prefers-color-scheme` is respected, and the visitor's explicit choice always
  wins. The scheme is applied before first paint, so there is no flash.
- Form errors are announced, tied to their field, and never colour-only.

## Security

- Every form: nonce → honeypot → time trap → per-IP rate limit → typed
  validation → sanitised storage.
- IP addresses are stored only as a salted one-way hash.
- Security headers (`X-Content-Type-Options`, `X-Frame-Options`,
  `Referrer-Policy`, `Permissions-Policy`, HSTS on TLS) — disable with the
  `treats_send_security_headers` filter if your server sets them already.
- XML-RPC off, author enumeration blocked, REST user endpoint closed to
  anonymous requests, login errors made generic, dashboard file editing off.

## Filters

| Filter | Purpose |
| --- | --- |
| `treats_home_sections` | Reorder or remove home page sections |
| `treats_schema_graph` | Modify the JSON-LD graph before output |
| `treats_send_security_headers` | Turn off theme-sent security headers |
| `treats_remove_core_block_css` | Keep core block styles |
| `treats_needs_form_assets` | Skip form CSS/JS on views without a form |
| `treats_ip_headers` | Trust a proxy header behind a load balancer |
| `treats_force_reduced_motion` | Disable animations site-wide |
| `treats_form_submitted` | Action fired after a submission is stored and emailed |

## How it was tested

The theme was built against a real WordPress 6.7 install and driven with a
headless browser rather than eyeballed:

- **42 interaction tests** — mobile drawer (open, focus trap, <kbd>Esc</kbd>,
  submenus), colour-scheme toggle and persistence, sticky/hiding header,
  scroll reveals under slow, fast and jump-to-bottom scrolling, menu search
  and filters, Click & Collect basket including persistence across reloads,
  FAQ accordion and topic filter, click-to-load map, gallery lightbox.
- **15 form tests** — client validation, honeypot, time trap, rate limiting,
  every form's success path, and the no-JavaScript fallback with JS disabled.
- **axe-core** on nine pages × light/dark × desktop/mobile: no violations.
- **Layout** — no horizontal overflow at 390px on any page.
- **Admin** — every custom post type screen, the Customizer and the menu
  editor load without a warning; submission records show their stored fields.
- **Performance** — 10–12 requests, ~230–290KB uncompressed, CLS 0.

## File map

```
treats-tea-room/
├── style.css              Theme header (styles live in /css)
├── functions.php          Bootstrap — loads /inc modules
├── header.php footer.php
├── front-page.php index.php page.php single.php
├── archive.php search.php 404.php searchform.php comments.php
├── screenshot.png
├── css/     main · menu · forms · print · editor
├── js/      main · menu · forms · customizer
├── fonts/   Cormorant Garamond, Inter, Pinyon Script (woff2)
├── images/  favicon, app icons, social card
├── inc/     setup, enqueue, template-tags, post-types, meta-boxes,
│            customizer, nav-walker, seo, schema, performance,
│            security, forms, activation
├── page-templates/  menu, booking, vouchers, about, contact, faq,
│                    gallery, full-width
├── template-parts/  components/ content/ home/ menu/
└── languages/       treats.pot
```

## Before launch

1. Replace the starter menu items with real dishes and prices.
2. Confirm the opening hours, phone and email in the Customizer. Defaults are
   taken from the approved design: 10/11 Silver Street, DH1 3RD,
   0191 386 0925, info@treatstearoom.co.uk, Mon–Sun 8:30–17:00, est. 1984.
3. Upload photography (see `images/README.md` for the crops the layout wants).
4. Set the notification email so bookings reach a monitored inbox, and send a
   test booking to confirm `wp_mail()` is delivering. On most hosts you want an
   SMTP plugin here.
5. Have the Privacy and Accessibility pages reviewed — both ship as drafts of
   sensible copy, not legal advice.
6. Set up 301 redirects from the old Enfold URLs to the new ones.

## Licence

GPL-2.0-or-later, matching WordPress. Cormorant Garamond, Inter and Pinyon
Script are all licensed under the SIL Open Font License 1.1.
