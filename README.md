silverstripe-embedlyfield
=========================

EmbedlyField. A module that uses embed.ly's wonderful API to retrieve the embed code for a large variety of video providers.

Notice
======

This is still experimental and a work in progress.

Usage
=====

Export the folder in to your root directory and take it from there. For high volume requests to Embed.ly, you will need an API key:

The code is documented using PHPDoc so any decent editor will pick up the annotations.

The simplest way of using it is to create 2 fields on your DataObject, URL and EmbedCode. Both are optional but without EmbedCode, the field is quite useless ;-)

    new EmbedlyField($this, 'URL', 'EmbedCode');

More details about Embed.ly are here: http://embed.ly/