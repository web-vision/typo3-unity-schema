.. include:: /Includes.rst.txt

.. _installation:

============
Installation
============

Install the extension through Composer:

.. code-block:: bash

   composer require web-vision/unity-schema

Include the TypoScript
======================

The extension ships the TypoScript that builds the head data response. Include it
in your site package's template, **after** ``EXT:wv_t3unity``'s and
``EXT:schema``'s TypoScript:

.. code-block:: typoscript

   @import 'EXT:wv_t3unity/Configuration/TypoScript/'
   @import 'EXT:schema/Configuration/TypoScript/'
   @import 'EXT:unity_schema/Configuration/TypoScript/'

Alternatively, add the static includes through the
:guilabel:`Template > Info/Modify > Includes` module.

.. note::

   Order matters: ``unity_schema`` extends the ``UnityHead`` page object defined
   by ``wv_t3unity``, and your own overrides (see :ref:`configuration`) must come
   last so they win.
