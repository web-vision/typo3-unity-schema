# TYPO3 extension `unity_schema`

schema.org integration for [TYPO3 Unity](https://github.com/web-vision/wv_t3unity)
(`web-vision/wv_t3unity`). It enriches Unity's headless "head data" JSON response with
rich structured data (JSON-LD), rendered through
[EXT:schema](https://extensions.typo3.org/extension/schema) (`brotkrueml/schema`).

## What it does

- Adds a `WebPage` (or a more specific subtype, e.g. `AboutPage`, `FAQPage`, ...) schema.org node
  for every page, based on the page's `tx_schema_webpagetype` field (provided by EXT:schema).
- Reliably exposes EXT:schema's own `RenderAdditionalTypesEvent` inside Unity's head request, so any
  PSR-14 listener that adds structured data through the documented EXT:schema API actually gets
  rendered - see [DEVELOPER.md](DEVELOPER.md) for why this needs an extra step in Unity.
- Lets editors and integrators turn any content element into a schema.org type (Article, Product,
  SoftwareApplication, a Question for an FAQPage, ...) via a few TCA fields, without any PHP - see
  [INTEGRATOR.md](INTEGRATOR.md).
- Provides a small extension point (`StructuredDataProviderInterface`) plus reusable helpers
  (content element fetching, FAL image metadata) for structured data that needs repeatable child
  items (carousels, FAQ question lists, product offers, ...) - see [DEVELOPER.md](DEVELOPER.md).

## Requirements

- TYPO3 `^12.4`
- PHP `^8.1`
- `brotkrueml/schema` `^3.15`
- `web-vision/wv_t3unity`

## Installation

```bash
composer require web-vision/unity-schema
```

Then include the extension's TypoScript (`EXT:unity_schema/Configuration/TypoScript/`) in your site
package, alongside `EXT:wv_t3unity`'s and `EXT:schema`'s TypoScript.

## Documentation

- [INTEGRATOR.md](INTEGRATOR.md) - for editors/integrators: which fields to fill in, worked examples
  per schema.org type, and where the limits of the no-code approach are.
- [DEVELOPER.md](DEVELOPER.md) - for developers: the Unity-specific architecture quirk this extension
  works around, and how to implement custom structured data providers for a consuming project.
