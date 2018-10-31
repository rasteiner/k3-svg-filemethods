<?php 

namespace rasteiner\k3_svg_filemethods;

use Kirby\Cms\App;
use Kirby\Cms\Model;
use Kirby\Cms\Filename;
use Kirby\Toolkit\F;
use Kirby\Data\Data;
use \Imagick;
use \Throwable;
use Kirby\Toolkit\Str;

class SVG {
    protected static $defaultComponents = null;

    protected static function get_default_component($name) {
        if (self::$defaultComponents === null) {
            self::$defaultComponents = include kirby()->root('kirby') . '/config/components.php';
        }
        return self::$defaultComponents[$name];
    }

    public static function trim($contents) {
        $magick = new Imagick();
        $magick->readImageBlob($contents);
        $magick->trimImage(0);

        $imagePage = $magick->getImagePage();
        $dimensions = $magick->getImageGeometry();

        $minXOut = $imagePage['x'];
        $minYOut = $imagePage['y'];
        $widthOut = $dimensions["width"];
        $heightOut = $dimensions["height"];

        $xml = simplexml_load_string($contents);

        $xml["viewBox"] = "$minXOut $minYOut $widthOut $heightOut";
        return $xml->asXML();
    }

    private static function rotatePoint($x, $y, $deg) {
        $cosdeg = cos($deg);
        $sindeg = sin($deg);

        $rotx = $x * $cosdeg - $y * $sindeg;
        $roty = $x * $sindeg - $y * $cosdeg;
        return [$rotx, $roty];
    }

    private static function rotate_bounds($x1, $y1, $w, $h, $deg) {
        $x2 = $x1 + $w;
        $y2 = $y1;
        $x3 = $x2;
        $y3 = $y1 + $h;
        $x4 = $x1;
        $y4 = $y3;

        $cosdeg = cos(deg2rad($deg));
        $sindeg = sin(deg2rad($deg));

        //point 1
        $x1r = $x1 * $cosdeg - $y1 * $sindeg;
        $y1r = $x1 * $sindeg + $y1 * $cosdeg;

        //point 2
        $x2r = $x2 * $cosdeg - $y2 * $sindeg;
        $y2r = $x2 * $sindeg + $y2 * $cosdeg;

        //point 3
        $x3r = $x3 * $cosdeg - $y3 * $sindeg;
        $y3r = $x3 * $sindeg + $y3 * $cosdeg;

        //point 4
        $x4r = $x4 * $cosdeg - $y4 * $sindeg;
        $y4r = $x4 * $sindeg + $y4 * $cosdeg;

        $minx = min($x1r, $x2r, $x3r, $x4r);
        $miny = min($y1r, $y2r, $y3r, $y4r);
        $maxx = max($x1r, $x2r, $x3r, $x4r);
        $maxy = max($y1r, $y2r, $y3r, $y4r);
        return [$minx, $miny, $maxx - $minx, $maxy - $miny];
    }

    public static function rotate($contents, $deg) {
        
        $dom = new \DOMDocument();
        $dom->loadXML($contents);
        
        $deg = floatval($deg);

        $doc = $dom->documentElement;
        $group = $dom->createElement('g');
        $group->setAttribute('transform', "rotate($deg)");

        //does it have a viewBox? 
        if($vbox = $doc->getAttribute('viewBox')) {
            preg_match_all('|\d+\.?\d*|', $vbox, $matches, PREG_PATTERN_ORDER);
            if(count($matches[0]) === 4) {
                list($x, $y, $w, $h) = array_map('floatval', $matches[0]);
                $doc->setAttribute('viewBox', join(' ', static::rotate_bounds($x, $y, $w, $h, $deg)));
            }
        }

        $children = [];
        foreach($doc->childNodes as $child) {
            $children[] = $child;
        }
        foreach ($children as $child) {
            $group->appendChild($child);
        }

        $doc->appendChild($group);

        return $dom->saveXML();      
    }

    public static function component_url(App $kirby, Model $file, array $options) {

        if (isset($options['trimSVG']) || isset($options['rotateSVG'])) {
            if ($file->extension() == 'svg') {
                $parent = $file->parent();
                $mediaRoot = $parent->mediaRoot() . '/' . $file->mediaHash();

                $attributes = [
                    't' => isset($options['trimSVG']) ? 't' : false,
                    'r' => isset($options['rotateSVG']) ? 'r' . floatval($options['rotateSVG']) : false
                ];
                $attributes = array_filter($attributes, function ($a) {
                    return !!$a;
                });
                
                $dst = $mediaRoot . '/{{ name }}-' . implode('-', $attributes) . '.svg';
                $thumb = (new Filename($file->root(), $dst, []))->toString();
                $thumbName = basename($thumb);
                $job = $mediaRoot . '/.jobs/' . $thumbName . '.json';
                if (file_exists($thumb) === false) {
                    try {
                        Data::write($job, \array_merge($options, [
                            'filename' => $file->filename()
                        ]));
                    } catch (\Throwable $e) {

                    }
                }

                return $parent->mediaUrl() . '/' . $file->mediaHash() . '/' . $thumbName;
            }
        }

        //fallback to default
        return static::get_default_component('file::url')($kirby, $file, $options);
    }

    public static function component_thumb(App $kirby, string $src, string $dst, array $options) {
        if (isset($options['rotateSVG']) || isset($options['trimSVG'])) {
            $content = file_get_contents($src);

            if(isset($options['trimSVG'])) {
                $content = self::trim($content);
            }

            if (isset($options['rotateSVG'])) {
                $content = self::rotate($content, $options['rotateSVG']);
            }
            F::write($dst, $content);
            return $dst;
        }
            
        //fallback to default
        return static::get_default_component('thumb')($kirby, $src, $dst, $options);
    }
}