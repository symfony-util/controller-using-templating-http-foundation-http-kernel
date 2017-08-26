<?php

/*
 * This file is part of the Symfony-Util package.
 *
 * (c) Jean-Bernard Addor
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\FrameworkTwig;

class Identity
{
    public function __invoke($a)
    {
        dump($a);
        return $a;
    }
}
