# TrimSVG File method

K3 plugin. A file method that allows you to trim an svg file to its visible bounds.

## Install 

### Download Zip file

Copy plugin folder into `site/plugins`

### Composer
Run `composer require rasteiner/k3-trimsvg-filemethod`.

## Usage example

In a template, a page contains an svg file. 

```php
<?php if($img = $page->image('graphic.svg')): ?>
    <img src="<?php echo $img->trimSVG()->url() ?>" alt="">
<?php endif; ?>
```

## Issues
Currently this plugin requires you to **not** have any other plugin that registers a `file::url` or a `thumb` component.
