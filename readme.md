# TrimSVG File method

K3 plugin. File methods for SVG files.

## Install 

### Download Zip file

Copy plugin folder into `site/plugins`

### Composer
Run `composer require rasteiner/k3-svg-filemethods`.

## Features

 - Trim an svg file to its visible bounds
 - Rotate an svg file by any degree.

## Usage example

In a template, a page contains an svg file. 

```html+php
<?php if($img = $page->image('graphic.svg')): ?>
    <img src="<?php echo $img->trimSVG()->rotate(90)->url() ?>" alt="">
<?php endif; ?>
```

## Issues
Currently this plugin requires you to **not** have any other plugin that registers a `file::url` or a `thumb` component.
