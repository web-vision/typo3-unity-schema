.. include:: /Includes.rst.txt

.. _start:

=============================
schema.org to Unity connector
=============================

:Extension key:
   unity_schema

:Package name:
   web-vision/unity-schema

:Version:
   |release|

:Language:
   en

:Author:
   web-vision GmbH

:License:
   This document is published under the
   `Open Publication License <https://www.opencontent.org/openpub/>`__.

----

schema.org integration for `TYPO3 Unity <https://github.com/web-vision/wv_t3unity>`__
(``web-vision/wv_t3unity``). It enriches Unity's headless "head data" JSON response
with structured data (JSON-LD), rendered through
`EXT:schema <https://extensions.typo3.org/extension/schema>`__ (``brotkrueml/schema``).

This manual is aimed at **integrators**: it describes the fields, TypoScript and
extension configuration you touch to add and adjust structured data - no PHP required.
Structured data that needs repeatable child objects (a list of offers, several FAQ
answers, ...) is a developer task and out of scope here.

----

**Table of contents**

.. toctree::
   :maxdepth: 2
   :titlesonly:

   Introduction/Index
   Installation/Index
   Configuration/Index
   Usage/Index
