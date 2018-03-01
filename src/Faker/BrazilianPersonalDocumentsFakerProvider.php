<?php
/**
 * Created by rodrigobrun
 *   with PhpStorm
 */

namespace Newestapps\Dev\Faker;

use Faker\Provider\Base;

class BrazilianPersonalDocumentsFakerProviderProvider extends Base
{

    public function randomCPF($formatted = false)
    {
        return nwdev_randomCPF($formatted);
    }

    public function randomCNPJ($formatted = false)
    {
        return nwdev_randomCNPJ($formatted);
    }

}