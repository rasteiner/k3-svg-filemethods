<?php

require_once __DIR__ . '/src/SVG.php';

use rasteiner\k3_svg_filemethods\SVG;

Kirby::plugin('rasteiner/k3-svg-filemethods', [
    'components' => [
        'file::url' => [SVG::class, 'component_url'],
        'thumb' => [SVG::class, 'component_thumb']
    ],
    'fileMethods' => [
        'trimSVG' => function () {
            if ($this->extension() !== 'svg') {
                return null;
            }

            return $this->thumb(['trimSVG' => true]);
        },
        'rotateSVG' => function ($deg) {
            if ($this->extension() !== 'svg') {
                return null;
            }

            return $this->thumb(['rotateSVG' => floatval($deg)]);
        }
    ]
]);