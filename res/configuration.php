<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2014 Markus Fischer <markus@fischer.name>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/*
 * This configuration maps a top level class name (Controller) to one ore more
 * properties ('components', 'uses') and provides a callback how to transforme
 * a found symbol (e.g. convert 'Foo' into 'FooComponent')
 */

$noTransform = function ($sym) { return $sym; };

return [
  'Controller' => [
    'components' => function ($sym) { return $sym . 'Component'; },
    'uses'       => $noTransform,
  ],
  'Component' => [
    'components' => function ($sym) { return $sym . 'Component'; },
  ],
  'Helper'     => [
    'components' => function ($sym) { return $sym . 'Component'; },
    'helpers'    => function ($sym) { return $sym . 'Helper'; },
    'uses'       => $noTransform,
  ],
  'Model'      => [
    'belongsTo'           => $noTransform,
    'hasAndBelongsToMany' => $noTransform,
    'hasOne'              => $noTransform,
    'hasMany'             => $noTransform,
  ],
  'Shell'      => [
    'uses' => $noTransform,
  ]
];
