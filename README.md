# Postio Address Validation

UK address autocomplete for WordPress. Drops onto WooCommerce checkout
and the major form builders so customers fill an address in two
keystrokes instead of twenty, backed by Royal Mail PAF data through the
[Postio](https://postio.co.uk) API.

The plugin is free and GPL. The API is metered pay-as-you-go — searching
costs nothing, you pay only when a customer picks an address.

## How it wires up

**Automatic**, where the builder has a real address field — nothing to
configure beyond saving your key:

WooCommerce (block and classic checkout, plus My Account → Addresses) ·
Gravity Forms · Fluent Forms · Forminator · WPForms *(the Address field
is part of WPForms Pro)*

**One class per field**, where the builder has no address field to
detect. The settings screen prints the exact classes for whichever of
these you have installed:

Contact Form 7 · Ninja Forms · Elementor Forms *(part of Elementor Pro)*

**Anything else** — Custom Mappings binds any search box to any set of
output fields by CSS selector, which covers page builders, bespoke
themes and hand-written forms.

There is also a block, `Postio Address Finder`, for one-off pickers.

## Privacy

The plugin talks to one host, `api.postio.co.uk`, and only while a
visitor is actively using an address field or an administrator presses
**Test connection**. No analytics, no usage tracking, no phoning home.
Nothing executable is downloaded at runtime — the autocomplete script is
bundled at `assets/address-finder.js`.

## Local development

Requires Docker.

```bash
npx @wordpress/env start
open http://localhost:8888     # admin / password
```

`.wp-env.json` pre-installs WooCommerce and every free builder above, so
the detectors have something to detect. Gravity Forms and Elementor Pro
are paid and must be supplied yourself.

## Building a release

```bash
./bin/build-zip.sh   # -> dist/postio-address-validation.zip
```

Stages the plugin under its WP.org slug, excludes everything in
`.distignore`, and produces a reproducible archive — the same commit
always yields the same checksum, which is printed on completion.

`.wporg-assets/` holds the banner, icon and screenshots. Those belong in
the SVN `assets/` directory at the repository root, a sibling of
`trunk/` and `tags/`, and deploy independently of any code release.

## The bundled script

`assets/address-finder.js` is a build of `@postio/address-finder-bundled`
(MIT). Its complete original TypeScript is embedded in the source map
beside it, `assets/address-finder.js.map`.

## Support

Email <admin@postio.co.uk>, or open an issue here.

## Licence

GPLv2 or later — see [`LICENSE`](./LICENSE). Postio is a trading name of
Onno Group Limited, registered in England & Wales, company no. 08622799.
