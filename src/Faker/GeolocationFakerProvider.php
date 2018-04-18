<?php
/**
 * Created by rodrigobrun
 *   with PhpStorm
 */

namespace Newestapps\Dev\Faker;

use Faker\Provider\Base;

class GeolocationFakerProvider extends Base
{

    public function randomGeoPoint($centre = [-25.5044715, -54.5367207], $radius = 10.0)
    {
        return nwdev_generateRandomGeoPoint($centre, $radius);
    }

}