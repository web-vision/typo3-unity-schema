# Integrator guide

This extension adds structured data (schema.org, rendered as JSON-LD) to the `jsonLd` key of
Unity's head data JSON response (`?type=3210`). There are three places to configure it, from most to
least generic.

## 1. Page type (`tx_schema_webpagetype`)

Every page has a "Type of web page" field (added by `brotkrueml/schema`, under the page properties'
SEO tab). It controls the root schema.org type of the page, for example `WebPage`, `AboutPage`,
`ContactPage`, `FAQPage`, `ProfilePage`, `CollectionPage`, ... Leave it empty for the generic
`WebPage`.

This type always gets `@id`, `name`, `datePublished` and `dateModified` filled in automatically.

## 2. Sitewide/organization-level TypoScript

For structured data that is not tied to a specific page or content element (for example a global
`Organization` node), use the `schema` TypoScript key under `UnityHead.10`:

```typoscript
UnityHead.10 {
    schema {
        10 = SCHEMA
        10 {
            type = Organization
            properties {
                name = ACME Inc.
                url = https://acme.com/
                logo = https://acme.com/fileadmin/logo.png
            }
        }
    }
}
```

Anything placed here is rendered as its own top-level node, in addition to the page's `WebPage`
node. See EXT:schema's own TypoScript documentation (`SCHEMA` cObject, under
`https://docs.typo3.org/p/brotkrueml/schema/main/en-us/`) for all available options. As noted there,
this cannot express repeatable structures (a list of offers, several FAQ questions, ...) - use
option 3 for those.

## 3. Per content element (`tx_schema_type`)

Every content element has a "Structured data (schema.org)" palette with three fields:

- **Schema.org type** (`tx_schema_type`): pick a schema.org type. Leave empty to not add any
  structured data for this content element. The list contains every type known to EXT:schema.
- **Is main entity of page** (`tx_schema_is_main_entity`): if checked, the type is added as the
  page's `mainEntity` instead of a standalone node. Several content elements on the same page can
  each be a main entity - this is exactly how several FAQ questions end up under one `FAQPage`, see
  the example below.
- **Schema.org properties (JSON)** (`tx_schema_properties`): a flat JSON object of property
  name/value pairs for the selected type.

### What the JSON field can and cannot express

The JSON field only supports **plain strings, numbers and booleans** (and lists thereof, e.g.
`sameAs`). It cannot express a property whose value must be *another* schema.org type - for
example `offers`, `acceptedAnswer`, `aggregateRating`, or an `author` given as a `Person` object.
For those, ask a developer to implement a `StructuredDataProviderInterface` (see
[DEVELOPER.md](DEVELOPER.md)) for that content element type.

### Worked examples

**Article** (e.g. on a `text` content element used as a blog post intro):

- Schema.org type: `Article` (or `BlogPosting`, `NewsArticle`)
- Is main entity of page: yes
- Properties:
  ```json
  {
      "headline": "How we redesigned our checkout",
      "description": "A behind-the-scenes look at our checkout redesign.",
      "image": "https://acme.com/fileadmin/checkout-hero.jpg",
      "datePublished": "2026-06-01",
      "author": "Jane Doe"
  }
  ```

**Product** (name/description/sku only - see above for why `offers`/`aggregateRating` need a
developer):

- Schema.org type: `Product`
- Is main entity of page: yes
- Properties:
  ```json
  {
      "name": "Comfy Chair",
      "description": "A very comfortable chair.",
      "sku": "CHAIR-001",
      "image": "https://acme.com/fileadmin/chair.jpg"
  }
  ```

**SoftwareApplication**:

- Schema.org type: `SoftwareApplication`
- Is main entity of page: yes
- Properties:
  ```json
  {
      "name": "ACME App",
      "applicationCategory": "BusinessApplication",
      "operatingSystem": "iOS, Android"
  }
  ```

**Profile page (`Person`)**: set the *page's* type (see section 1) to `ProfilePage`, then tag one
content element on that page as the main-entity `Person`:

- Schema.org type: `Person`
- Is main entity of page: yes
- Properties:
  ```json
  {
      "givenName": "Jane",
      "familyName": "Doe",
      "jobTitle": "Head of Sales",
      "sameAs": ["https://www.linkedin.com/in/jane-doe"]
  }
  ```

**FAQ (`Question`), name only**: set the page's type to `FAQPage`, then tag every question content
element as a main-entity `Question`:

- Schema.org type: `Question`
- Is main entity of page: yes
- Properties: `{"name": "How long is the delivery time?"}`

This alone is *not* enough for Google's FAQ rich result, which additionally requires an
`acceptedAnswer` (an `Answer` object) per question - that nested object needs a developer-provided
provider, see [DEVELOPER.md](DEVELOPER.md).

## Container elements

If your site uses `b13/container` (or any extension nesting content elements inside a parent content
element), ask a developer to set the `containerParentField` extension configuration
(`Settings > Extension Configuration > unity_schema`) to the field name holding the parent's uid
(for `b13/container` this is `tx_container_parent`). Without it, content elements nested inside a
container are not picked up for structured data.
