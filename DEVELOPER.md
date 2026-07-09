# Developer guide

## Architecture: why this extension exists

`web-vision/wv_t3unity` renders a page in (at least) two unrelated HTTP requests:

- `type=0` - the normal HTML page. Content elements render as HTML via Fluid
  (`fluid_styled_content` / Content Blocks).
- `type=3210` (`UnityHead`) - a JSON-only request
  (`WebVision\WvT3unity\UserFunc\ContentJson::getHeadData`) producing the head/SEO data. This is
  where `Classes/EventListener/SchemaOrgEventListener.php` runs and builds the JSON-LD.

Because `UnityHead` sets `config.disableAllHeaderCode = 1` and never renders a full HTML document,
EXT:schema's own page-render hook
(`Brotkrueml\Schema\Hooks\PageRenderer\SchemaMarkupInjection`, bound to `PageRenderer`
`render-postProcess`) never gets a chance to run. That hook is what normally dispatches EXT:schema's
documented extension point, `Brotkrueml\Schema\Event\RenderAdditionalTypesEvent`, and embeds the
result into the page.

**Consequence:** a content element cannot contribute schema data by rendering something during the
`type=0` request - that request is irrelevant to the JSON-LD output. Structured data for a page has
to be assembled from scratch, independently, inside the `type=3210` request, by reading the page's
content elements directly from the database.

To keep the well-known EXT:schema extension point working anyway,
`SchemaOrgEventListener::dispatchRenderAdditionalTypesEvent()` dispatches
`RenderAdditionalTypesEvent` itself (mirroring what `SchemaMarkupInjection` does), right after the
page's `WebPage` type is built and before the JSON-LD is rendered. Any listener tagged for that
event - including this extension's own `StructuredDataFromContentEventListener` - fires from there.

## Building blocks

- **`Classes/Domain/Repository/PageContentRepository.php`** - fetches the visible `tt_content` rows
  of a page (via `ContentObjectRenderer::getRecords()`, so enable-fields, versioning and language
  overlay are handled the same way core `RECORDS`/`CONTENT` cObjects handle them). Optionally
  recurses into container children if the `containerParentField` extension configuration is set
  (see below) - this keeps the extension free of a hard dependency on any specific container
  extension.
- **`Classes/Type/ImageObjectFactory.php`** - turns a FAL file into a
  `Brotkrueml\Schema\Model\Type\ImageObject` (`contentUrl`, `width`, `height`, `caption`
  fallback chain, `copyrightHolder`). Reuse this wherever a provider needs to describe an image.
- **`Classes/StructuredData/StructuredDataProviderInterface.php`** - the extension point for
  turning one `tt_content` row into one or more schema.org types.
- **`Classes/StructuredData/StructuredDataProviderRegistry.php`** - collects every tagged provider
  (`unity_schema.structured_data_provider`) and resolves the ones matching a given row.
- **`Classes/StructuredData/Provider/GenericPropertiesProvider.php`** - the built-in provider driven
  by the `tx_schema_type` / `tx_schema_properties` TCA fields (see [INTEGRATOR.md](INTEGRATOR.md)).
  Reference implementation for the interface above.
- **`Classes/EventListener/StructuredDataFromContentEventListener.php`** - orchestrates the above:
  resolves the current page id, fetches its content elements, asks the registry for matching
  providers per row, and adds the resulting types via `$event->addType()` or
  `$event->addMainEntityOfWebPage()` (depending on the row's `tx_schema_is_main_entity` field).

## Implementing a custom provider

Anything the generic JSON-properties field cannot express - repeatable child items (a carousel's
items, several FAQ questions with answers) or any property whose value must be another schema.org
type (`offers`, `acceptedAnswer`, `aggregateRating`, an `author` given as a `Person`) - needs a
dedicated provider in the consuming extension's own site package.

Example: a carousel content element (matched by its own `CType`) rendered as a Google-style
carousel. Google's carousel rich result is *not* a "Carousel" schema.org type - it is an `ItemList`
of `ListItem`s, each wrapping the actual entity (here: `ImageObject`), see
[Google's carousel guidelines](https://developers.google.com/search/docs/appearance/structured-data/carousel).

```php
<?php

declare(strict_types=1);

namespace Vendor\Site\StructuredData\Provider;

use Brotkrueml\Schema\Core\Model\TypeInterface;
use Brotkrueml\Schema\Model\Type\ImageObject;
use Brotkrueml\Schema\Model\Type\ItemList;
use Brotkrueml\Schema\Model\Type\ListItem;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Resource\FileRepository;
use WebVision\UnitySchema\StructuredData\StructuredDataProviderInterface;
use WebVision\UnitySchema\Type\ImageObjectFactory;

final class CarouselProvider implements StructuredDataProviderInterface
{
    public function __construct(
        private readonly FileRepository $fileRepository,
        private readonly ImageObjectFactory $imageObjectFactory,
    ) {
    }

    public function supports(array $contentRecord): bool
    {
        return ($contentRecord['CType'] ?? '') === 'my_carousel';
    }

    public function provide(array $contentRecord, ServerRequestInterface $request): iterable
    {
        $items = [];
        $position = 1;
        foreach ($this->fileRepository->findByRelation('tt_content', 'image', $contentRecord['uid']) as $file) {
            $items[] = (new ListItem())->setProperties([
                'position' => $position++,
                'item' => $this->imageObjectFactory->fromFile($file, $request),
            ]);
        }

        yield (new ItemList())->setProperties(['itemListElement' => $items]);
    }
}
```

Register it like any other autowired service - it is picked up automatically because it implements
`StructuredDataProviderInterface`:

```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true
    public: false

  Vendor\Site\:
    resource: '../Classes/*'
```

Nothing else is required - `StructuredDataFromContentEventListener` will call `supports()` for every
content element on the page and `provide()` for the matching ones.

This works across package boundaries - a provider in a consuming extension gets tagged for
`unity_schema.structured_data_provider` automatically, without that extension having to declare the
tag itself.

## Extension configuration

- **`containerParentField`** (`ext_conf_template.txt`): the `tt_content` field holding the uid of a
  parent container element, if your site nests content elements inside a container (e.g.
  `tx_container_parent` for `b13/container`). Leave empty (default) to only look at top-level
  content elements of a page.
