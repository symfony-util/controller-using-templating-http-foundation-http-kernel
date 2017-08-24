<?php

/*
 * This file is part of the Symfony-Util package.
 *
 * (c) Jean-Bernard Addor
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Component;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Templating\EngineInterface;

class EngineAsArgumentFrameworkController extends Controller
{
    public function __construct($template = 'index.html.twig')
    {
        $this->template = $template;
    }

    public function __invoke(EngineInterface $templating) // symfony >= 3.3
    {
        return new Response($templating->render($this->template));
    }
}
