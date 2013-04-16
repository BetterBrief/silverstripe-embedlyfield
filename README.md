silverstripe-embedlyfield
=========================

EmbedlyField. A module that uses embed.ly's wonderful API to retrieve the embed code for a large variety of video providers.

Notice
======

This is still experimental and a work in progress but we've deployed it to a few live sites and it works great.

Usage
=====

Export the folder in to your root directory and take it from there. For high volume requests to Embed.ly, you will need an API key.

The code is documented using PHPDoc so any decent editor will pick up the annotations.

The simplest way of using it is to create 2 fields on your DataObject, URL and EmbedCode. Both are optional but without EmbedCode, the field is quite useless ;-)

    new EmbedlyField($this, 'URL', 'EmbedCode', 'Title of the field', $options);

You can set some options in the 5th parameter as an associative array:

- EmbedWidth: The width of the embed code's video. (Note that you can usually resize the iframe on the frontend.)
- ThumbnailField: If you wish to save a thumbnail of the video, the value of this will correspond with the db field of the DataObject $model you set as the 0th parameter. (Note that this is written only when the field is saved.)
- ApiArgs: An associative array of API arguments for the oembed call. A list of those arguments can be found here: http://embed.ly/docs/embed/api/arguments

More details about Embed.ly are here: http://embed.ly/
