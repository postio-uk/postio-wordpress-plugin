# WP.org listing assets

These are **not** part of the plugin zip — WP.org serves them from the
`assets/` folder at the **root** of the SVN repo (sibling to `trunk/`
and `tags/`), and they deploy independently of any code release. A new
screenshot can go live without a version bump.

| File | Where it goes |
|---|---|
| `banner-772x250.png` | `assets/banner-772x250.png` |
| `banner-1544x500.png` | `assets/banner-1544x500.png` (retina) |
| `icon-128x128.png` | `assets/icon-128x128.png` |
| `icon-256x256.png` | `assets/icon-256x256.png` |
| `screenshot-N.png` | `assets/screenshot-N.png`, numbered to match readme.txt |

`banner.svg` and `icon.svg` are the sources. Regenerate with:

```bash
rsvg-convert -w 772  -h 250 banner.svg -o banner-772x250.png
rsvg-convert -w 1544 -h 500 banner.svg -o banner-1544x500.png
rsvg-convert -w 256  -h 256 icon.svg   -o icon-256x256.png
rsvg-convert -w 128  -h 128 icon.svg   -o icon-128x128.png
```

Colours are the Postio brand tokens from `postio-www/app/globals.css`
(navy-950 `#04080F`, navy-900 `#080F1C`, navy-700 `#162038`, green-500
`#22C55E`, slate-50 `#F5F6F8`, slate-300 `#A0A3B0`). Fonts are Lato and
Liberation Mono, both present on the dev box — the real brand faces are
Geist Sans/Mono, so swap them in if they ever land here.

The banner headline is sized to clear the mock autocomplete card at
772px wide. If you edit the copy, re-render and check it at the small
size, not just the retina one.
