# controller-using-templating-http-foundation-http-kernel
Controller which renders a template and returns a response (HttpFoundation): dependency check for use by http-kernel

[![PHPPackages Rank](https://phppackages.org/p/symfony-util/controller-using-templating-http-foundation-http-kernel/badge/rank.svg)](https://phppackages.org/p/symfony-util/controller-using-templating-http-foundation-http-kernel)
[![Monthly Downloads](https://poser.pugx.org/symfony-util/controller-using-templating-http-foundation-http-kernel/d/monthly)](https://packagist.org/packages/symfony-util/controller-using-templating-http-foundation-http-kernel)
[![PHPPackages Referenced By](https://phppackages.org/p/symfony-util/controller-using-templating-http-foundation-http-kernel/badge/referenced-by.svg)](https://phppackages.org/p/symfony-util/controller-using-templating-http-foundation-http-kernel)
[![Tested PHP Versions](https://php-eye.com/badge/symfony-util/controller-using-templating-http-foundation-http-kernel/tested.svg)](https://php-eye.com/package/symfony-util/controller-using-templating-http-foundation-http-kernel)
[![Dependency Status](https://www.versioneye.com/php/symfony-util:controller-using-templating-http-foundation-http-kernel/badge)](https://www.versioneye.com/php/symfony-util:controller-using-templating-http-foundation-http-kernel)
[![Build Status](https://travis-ci.org/symfony-util/controller-using-templating-http-foundation-http-kernel.svg?branch=master)](https://travis-ci.org/symfony-util/controller-using-templating-http-foundation-http-kernel)

[![Scrutinizer](https://scrutinizer-ci.com/g/symfony-util/controller-using-templating-http-foundation-http-kernel/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/symfony-util/controller-using-templating-http-foundation-http-kernel/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/20fdf909-deda-46fa-9fb5-dc2cdf778c05/mini.png)](https://insight.sensiolabs.com/projects/20fdf909-deda-46fa-9fb5-dc2cdf778c05)
<!---
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/20fdf909-deda-46fa-9fb5-dc2cdf778c05.svg)](https://insight.sensiolabs.com/projects/20fdf909-deda-46fa-9fb5-dc2cdf778c05)
-->

## Usefullness
* To be sure than Symfony >= 3.3 as required to call the controller with templating engin as argument.
* Additional functional testing calling the controller from HttpKernel or FrameworkBundle.

## Possible improvements
* Does not pass test with Symfony 4. Travis 445, using Symfony 4, shows mixed components from Symfony 3 and 4 and fails all tests due to bad configuration. Symfony 3.4 fails in a may be similar way. 
* Use (yaml) configuraton for test Kernel(s), instead of in php code configuration
* Tests do not use 2 Kernels as intended, the first one configured is the only one! Corrected, OK now!

### Should be a composer *metapackage*
* [metapackage](https://getcomposer.org/doc/04-schema.md#type)
* Maybe all the PHP code has to be moved somewhere else!

Icon: https://material.io/icons/#ic_wallpaper
