.. include:: /Includes.rst.txt

.. _configuration:

=============
Configuration
=============

Everything an integrator adjusts lives in TypoScript or in the extension
configuration. The per-page and per-content-element fields are covered
separately in :ref:`usage`.

.. contents::
   :local:

.. _configuration-extension:

Extension configuration
=======================

Set under :guilabel:`Admin Tools > Settings > Extension Configuration >
unity_schema`.

.. confval:: containerParentField

   :type: string
   :Default: (empty)

   Field name on ``tt_content`` holding the uid of a parent container element -
   for example ``tx_container_parent`` from ``b13/container``. Set it so content
   elements nested inside a container are still collected for structured data.
   Leave it empty to disable recursing into container children.

.. _configuration-head-response:

The head data response
======================

Structured data is delivered under the ``jsonLd`` key of the head data JSON,
served under page type ``3210``:

.. code-block:: bash

   curl 'https://example.com/some-page?type=3210'

.. code-block:: json

   {
       "title": "Some page",
       "jsonLd": "{\"@context\":\"https://schema.org/\",\"@type\":\"WebPage\", ...}"
   }

.. _configuration-sitewide:

Sitewide nodes (for example ``Organization``)
=============================================

For structured data that is not tied to a specific page or content element, add a
:typoscript:`SCHEMA` content object under ``UnityHead.10.schema``. Anything placed
here is rendered as its own top-level node, in addition to the page's ``WebPage``
node:

.. code-block:: typoscript

   UnityHead.10.schema {
       20 = SCHEMA
       20 {
           type = Organization
           properties {
               name = ACME Inc.
               url = https://acme.com/
               logo = https://acme.com/fileadmin/logo.png
           }
       }
   }

.. tip::

   ``10`` is reserved for the page's ``WebPage`` node (see
   :ref:`configuration-webpage-id`). Use ``20``, ``30``, ... for your own nodes.

See the `EXT:schema TypoScript reference
<https://docs.typo3.org/p/brotkrueml/schema/main/en-us/TypoScript/Index.html>`__
for all :typoscript:`SCHEMA` options. As noted there, it cannot express
repeatable structures (a list of offers, several FAQ questions, ...); those need a
developer-provided provider.

.. _configuration-webpage-id:

Overriding the ``WebPage`` ``@id`` (storefront URL)
===================================================

By default the ``@id`` of the ``WebPage`` node is the absolute TYPO3 page URL. In
a decoupled setup the canonical URL is usually the storefront (for example a
Magento domain), not the CMS. The ``@id`` is built through the typolink at
``UnityHead.10.schema.10.id.typolink``, so you can attach a typolink ``userFunc``
that rewrites the resolved URL - per page - without touching this extension:

.. code-block:: typoscript

   UnityHead.10.schema.10.id.typolink {
       # hand the bare page path to the userFunc, which prepends the storefront base
       forceAbsoluteUrl = 0
       userFunc = Vendor\SitePackage\UserFunc\ManipulateTypoLinkResult->enrichWithStorefrontUrl
   }

The ``userFunc`` receives the resolved
:php:`TYPO3\CMS\Frontend\Typolink\LinkResultInterface` and returns a rewritten
one:

.. code-block:: php

   <?php

   declare(strict_types=1);

   namespace Vendor\SitePackage\UserFunc;

   use Psr\Http\Message\ServerRequestInterface;
   use TYPO3\CMS\Frontend\Typolink\LinkResult;
   use TYPO3\CMS\Frontend\Typolink\LinkResultInterface;

   final class ManipulateTypoLinkResult
   {
       public function enrichWithStorefrontUrl(
           LinkResultInterface $content,
           array $conf,
           ServerRequestInterface $request,
       ): LinkResultInterface {
           $path = parse_url($content->getUrl(), PHP_URL_PATH);
           if (!is_string($path)) {
               return $content;
           }

           return new LinkResult($content->getType(), 'https://shop.example' . $path);
       }
   }

.. note::

   The URL-rewriting logic itself is project-specific (which environment or site
   maps to which storefront domain), so the ``userFunc`` body is yours to write.
   The extension only guarantees that whatever it returns becomes the final
   ``@id``.

.. _configuration-automatic-webpage:

Turning automatic ``WebPage`` generation off
=============================================

The automatic ``WebPage`` node is emitted only when EXT:schema's
``automaticWebPageSchemaGeneration`` setting is enabled (its default). Disable it
under :guilabel:`Extension Configuration > schema` to stop this extension from
adding the node - for example when you provide the page node entirely through your
own :ref:`sitewide TypoScript <configuration-sitewide>`.
