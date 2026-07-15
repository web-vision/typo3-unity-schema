.. include:: /Includes.rst.txt

.. _introduction:

============
Introduction
============

What it does
============

``unity_schema`` adds schema.org structured data (rendered as JSON-LD) to the
``jsonLd`` key of Unity's head data JSON response, which is requested under page
type ``3210`` (``?type=3210``). Concretely it:

*  Adds a ``WebPage`` node - or a more specific subtype such as ``AboutPage`` or
   ``FAQPage`` - for every page, based on the page's :ref:`Type of web page
   <usage-page-type>` field. The node always receives ``@id``, ``name``,
   ``datePublished`` and ``dateModified`` automatically.

*  Lets you turn any content element into a schema.org type (``Article``,
   ``Product``, a ``Question`` on an ``FAQPage``, ...) through a handful of TCA
   fields - see :ref:`usage`.

*  Lets you attach sitewide nodes (for example a global ``Organization``) and
   reshape the page ``@id`` through TypoScript - see :ref:`configuration`.

The ``@id`` of the ``WebPage`` node defaults to the TYPO3 page URL, but is
overridable so it can point at a decoupled storefront (for example a Magento
domain) instead - see :ref:`configuration-webpage-id`.

Requirements
============

*  TYPO3 ``^12.4``
*  PHP ``^8.1``
*  ``brotkrueml/schema`` ``^3.15``
*  ``web-vision/wv_t3unity``

.. note::

   Structured data whose value must be *another* schema.org object - ``offers``
   on a ``Product``, ``acceptedAnswer`` on a ``Question``, an ``author`` given as
   a ``Person`` - cannot be expressed through the fields and TypoScript described
   in this manual. Those need a developer-provided provider.
