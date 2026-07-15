.. include:: /Includes.rst.txt

.. _usage:

=====================================
Adding structured data through fields
=====================================

Two sets of TCA fields let editors and integrators add structured data without
any PHP: one on the page, one on each content element.

.. _usage-page-type:

Page type
=========

Every page has a :guilabel:`Type of web page` field (added by
``brotkrueml/schema``, under the page properties' :guilabel:`SEO` tab). It sets
the root schema.org type of the page - for example ``WebPage``, ``AboutPage``,
``ContactPage``, ``FAQPage``, ``ProfilePage`` or ``CollectionPage``. Leave it
empty for the generic ``WebPage``.

This node always gets ``@id``, ``name``, ``datePublished`` and ``dateModified``
filled in automatically.

.. _usage-content-element:

Per content element
===================

Every content element has a :guilabel:`Structured data (schema.org)` palette with
three fields:

:guilabel:`Schema.org type` (``tx_schema_type``)
   Pick a schema.org type. Leave empty to add no structured data for this content
   element. The list contains every type known to EXT:schema.

:guilabel:`Is main entity of page` (``tx_schema_is_main_entity``)
   If checked, the type is added as the page's ``mainEntity`` instead of a
   standalone node. Several content elements on the same page can each be a main
   entity - this is how several FAQ questions end up under one ``FAQPage``.

:guilabel:`Schema.org properties (JSON)` (``tx_schema_properties``)
   A flat JSON object of property name/value pairs for the selected type.

.. warning::

   The JSON field only supports **plain strings, numbers and booleans** (and
   lists thereof, e.g. ``sameAs``). It cannot express a property whose value must
   be *another* schema.org type - ``offers``, ``acceptedAnswer``,
   ``aggregateRating``, or an ``author`` given as a ``Person`` object. Those need
   a developer-provided provider.

Worked examples
===============

Article
-------

On a ``text`` element used as a blog post intro:

*  :guilabel:`Schema.org type`: ``Article`` (or ``BlogPosting``, ``NewsArticle``)
*  :guilabel:`Is main entity of page`: yes
*  :guilabel:`Properties`:

   .. code-block:: json

      {
          "headline": "How we redesigned our checkout",
          "description": "A behind-the-scenes look at our checkout redesign.",
          "image": "https://acme.com/fileadmin/checkout-hero.jpg",
          "datePublished": "2026-06-01",
          "author": "Jane Doe"
      }

Product
-------

Name, description and sku only - see the warning above for why ``offers`` and
``aggregateRating`` need a developer:

*  :guilabel:`Schema.org type`: ``Product``
*  :guilabel:`Is main entity of page`: yes
*  :guilabel:`Properties`:

   .. code-block:: json

      {
          "name": "Comfy Chair",
          "description": "A very comfortable chair.",
          "sku": "CHAIR-001",
          "image": "https://acme.com/fileadmin/chair.jpg"
      }

Profile page (``Person``)
-------------------------

Set the *page's* type (see :ref:`usage-page-type`) to ``ProfilePage``, then tag
one content element on that page as the main-entity ``Person``:

*  :guilabel:`Schema.org type`: ``Person``
*  :guilabel:`Is main entity of page`: yes
*  :guilabel:`Properties`:

   .. code-block:: json

      {
          "givenName": "Jane",
          "familyName": "Doe",
          "jobTitle": "Head of Sales",
          "sameAs": ["https://www.linkedin.com/in/jane-doe"]
      }

FAQ (``Question``), name only
-----------------------------

Set the page's type to ``FAQPage``, then tag every question content element as a
main-entity ``Question`` with a single ``name`` property:

.. code-block:: json

   {"name": "How long is the delivery time?"}

.. note::

   This alone is *not* enough for Google's FAQ rich result, which additionally
   requires an ``acceptedAnswer`` (an ``Answer`` object) per question. That nested
   object needs a developer-provided provider.

.. _usage-containers:

Container elements
==================

If your site nests content elements inside a parent element (``b13/container`` or
similar), set :ref:`containerParentField <configuration-extension>` to the field
holding the parent's uid. Without it, content elements inside a container are not
picked up for structured data.
