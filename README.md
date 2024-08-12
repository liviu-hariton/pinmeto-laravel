# PinMeTo Laravel package

A Laravel PHP package that provides convenient access to the [PinMeTo](https://www.pinmeto.com/) API, allowing users to interact with PinMeTo's locations data and metrics.

## Overview
Integration with PinMeTo offers the ability to fetch information and send updates through PinMeTo API for:

* Locations
* Insights (Google&trade; and Facebook&trade;)
* Keywords (Google&trade;)
* Reviews (Google&trade; and Facebook&trade;)

## Table Of Content
* [Requirements](#requirements)
* [Installation](#installation)
* [Usage](#usage)
  * [Get all available locations](#get-all-available-locations)
  * [Get a specific location](#get-a-specific-location)
  * [Create a new location](#create-a-new-location)
  * [Update an existing location](#update-an-existing-location)
  * [Get locations metrics data](#metrics)
  * [Get locations Google keywords](#google-keywords)
  * [Get locations ratings](#ratings)
  * [Get network categories](#network-categories)
* [License](#license)
* [PinMeTo official API documentation](#pinmeto-official-api-documentation)
* [Disclaimer](#disclaimer)
* [Standalone PHP library](#standalone-php-library)

## Requirements

* a [PinMeTo](https://www.pinmeto.com/login) user account with API access enabled
* PHP >= 8.1
* [Laravel](https://github.com/laravel/laravel) >= 10.0
* [Guzzle](https://github.com/guzzle/guzzle) >= 7.8

## Installation

You can install the PinMeTo Larvel package via Composer. Run the following command in your terminal:

```bash
composer require liviu-hariton/pinmeto-laravel
```
Laravel will automatically register the package.

Publish the config file of this package with this command (and choose `LHDev\PinmetoLaravel` from the presented list)

```bash
php artisan vendor:publish
```
The following config file will be published in `config/pinmeto.php`

```php
return [
    'app_id' => env('PINMETO_APP_ID', ''), // the PinMeTo `App ID`
    'app_secret' => env('PINMETO_APP_SECRET', ''), // the PinMeTo `App Secret`
    'account_id' => env('PINMETO_ACCOUNT_ID', ''), // the PinMeTo `Account ID`
    'mode' => env('PINMETO_MODE', 'test'), // the library working mode: `live` or `test` (defaults to `test`)
];
```
Edit your `.env` file and add the following to it:

```text
PINMETO_APP_ID=
PINMETO_APP_SECRET=
PINMETO_ACCOUNT_ID=
PINMETO_MODE=
````
You can get the `Account ID`, `App ID` and `App Secret` values from your [PinMeTo Account Settings](https://places.pinmeto.com/account-settings/)

The `PINMETO_MODE` can have one of the `test` or `live` values, depending on what stage of the PinMeTo API you want to use.

## Usage

When the installation is done you can easily retrieve locations data by using the available methods. All methods will return a JSON formatted data. Just inject the dependency in your controller's methods.

### Get all available locations

```php
<?php

namespace App\Http\Controllers;

use LHDev\PinmetoLaravel\Pinmeto;

class YourController extends Controller
{
    public function yourMethod(Pinmeto $pinmeto)
    {
        $locations = $pinmeto->getLocations();

        /* ... rest of your code ... */
    }
}
```

Optionally, you can also pass an array of parameters

```php
<?php

namespace App\Http\Controllers;

use LHDev\PinmetoLaravel\Pinmeto;

class YourController extends Controller
{
    public function yourMethod(Pinmeto $pinmeto)
    {
        $parameters = [
            'pagesize' => '2' // Number of locations that the request returns, default 100, max 250
            'next' => '569652a91151474860f5e173', // (string) Id of starting point to next page
            'before' => '569649b49c5ec8685e11175e', // (string) Id of starting point to previous page
        ];
        
        $locations = $pinmeto->getLocations($parameters);

        /* ... rest of your code ... */
    }
}
```

### Get a specific location

```php
<?php

namespace App\Http\Controllers;

use LHDev\PinmetoLaravel\Pinmeto;

class YourController extends Controller
{
    public function yourMethod(Pinmeto $pinmeto)
    {
        $store_id = 8;
        
        $location_data = $pinmeto->getLocation($store_id);

        /* ... rest of your code ... */
    }
}
```

### Create a new location

```php
<?php

namespace App\Http\Controllers;

use LHDev\PinmetoLaravel\Pinmeto;

class YourController extends Controller
{
    public function yourMethod(Pinmeto $pinmeto)
    {
        $parameters = [
            'name' => 'Your store name',
            'storeId' => 'your_store_id',
            'address' => [
                'street' => 'Store address',
                'zip' => 'Zipcode',
                'city' => 'The City',
                'country' => 'The Country',
            ],
            'location' => [
                'lat' => 59.333755678571,
                'lon' => 18.056143908447,
            ],
        ];
        
        $pinmeto->createLocation($parameters);

        /* ... rest of your code ... */
    }
}
```

You can also use the "Upsert" option by passing an additional parameter

```php
<?php

namespace App\Http\Controllers;

use LHDev\PinmetoLaravel\Pinmeto;

class YourController extends Controller
{
    public function yourMethod(Pinmeto $pinmeto)
    {
        $parameters = [
            'name' => 'Your store name',
            'storeId' => 'your_store_id',
            'address' => [
                'street' => 'Store address',
                'zip' => 'Zipcode',
                'city' => 'The City',
                'country' => 'The Country',
            ],
            'location' => [
                'lat' => 59.333755678571,
                'lon' => 18.056143908447,
            ],
        ];
        
        $pinmeto->createLocation($parameters, true);

        /* ... rest of your code ... */
    }
}
```

### Update an existing location

```php
<?php

namespace App\Http\Controllers;

use LHDev\PinmetoLaravel\Pinmeto;

class YourController extends Controller
{
    public function yourMethod(Pinmeto $pinmeto)
    {
        $store_id = 8;
        
        $parameters = [
            'name' => 'The new store name',
            'address' => [
                'street' => 'The new store address',
                'zip' => 'Some other zipcode',
                'city' => 'In some other city',
                'country' => 'In some other country',
            ],
        ];
        
        $pinmeto->updateLocation($store_id, $parameters);

        /* ... rest of your code ... */
    }
}
```

### Metrics

Get the Google&trade; or Facebook&trade; metrics data for all locations

```php
<?php

namespace App\Http\Controllers;

use LHDev\PinmetoLaravel\Pinmeto;

class YourController extends Controller
{
    public function yourMethod(Pinmeto $pinmeto)
    {
        $metrics = $pinmeto->getMetrics(
            source: 'google', // the source can be either `facebook` or `google`
            from_date: '2024-01-01', // the format is `YYYY-MM-DD`
            to_date: '2024-03-31', // the format is `YYYY-MM-DD`
            fields: [
                'businessImpressionsDesktopMaps', 'businessImpressionsDesktopSearch'
            ] // All available fields are described here https://api.pinmeto.com/documentation/v3/
        );

        /* ... rest of your code ... */
    }
}
```

or for a specific location by passing the Store ID

```php
<?php

namespace App\Http\Controllers;

use LHDev\PinmetoLaravel\Pinmeto;

class YourController extends Controller
{
    public function yourMethod(Pinmeto $pinmeto)
    {
        $metrics = $pinmeto->getMetrics(
            source: 'facebook', // the source can be either `facebook` or `google`
            from_date: '2024-01-01', // the format is `YYYY-MM-DD`
            to_date: '2024-03-31', // the format is `YYYY-MM-DD`
            store_id: 8
        );

        /* ... rest of your code ... */
    }
}
```

### Google keywords

Get the Google&trade; keywords data for all locations

```php
<?php

namespace App\Http\Controllers;

use LHDev\PinmetoLaravel\Pinmeto;

class YourController extends Controller
{
    public function yourMethod(Pinmeto $pinmeto)
    {
        $keywords = $pinmeto->getKeywords(
            from_date: '2024-01', // the format is `YYYY-MM`
            to_date: '2024-03' // the format is `YYYY-MM`
        );

        /* ... rest of your code ... */
    }
}
```

or for a specific location by passing the Store ID

```php
<?php

namespace App\Http\Controllers;

use LHDev\PinmetoLaravel\Pinmeto;

class YourController extends Controller
{
    public function yourMethod(Pinmeto $pinmeto)
    {
        $keywords = $pinmeto->getKeywords(
            from_date: '2024-01', // the format is `YYYY-MM`
            to_date: '2024-03', // the format is `YYYY-MM`
            store_id: 8
        );

        /* ... rest of your code ... */
    }
}
```

### Ratings

Get the Google&trade; or Facebook&trade; ratings data for all locations

```php
<?php

namespace App\Http\Controllers;

use LHDev\PinmetoLaravel\Pinmeto;

class YourController extends Controller
{
    public function yourMethod(Pinmeto $pinmeto)
    {
        $ratings = $pinmeto->getRatings(
            source: 'google', // the source can be either `facebook` or `google`
            from_date: '2024-01-01', // the format is `YYYY-MM-DD`
            to_date: '2024-03-31' // the format is `YYYY-MM-DD`
        );

        /* ... rest of your code ... */
    }
}
```

or for a specific location by passing the Store ID

```php
<?php

namespace App\Http\Controllers;

use LHDev\PinmetoLaravel\Pinmeto;

class YourController extends Controller
{
    public function yourMethod(Pinmeto $pinmeto)
    {
        $ratings = $pinmeto->getRatings(
            source: 'facebook', // the source can be either `facebook` or `google`
            from_date: '2024-01-01', // the format is `YYYY-MM-DD`
            to_date: '2024-03-31', // the format is `YYYY-MM-DD`
            store_id: 8
        );

        /* ... rest of your code ... */
    }
}
```

### Network categories

Get the list of categories per network. The available networks are `google` or `apple` or `facebook` or `bing`

```php

<?php

namespace App\Http\Controllers;

use LHDev\PinmetoLaravel\Pinmeto;

class YourController extends Controller
{
    public function yourMethod(Pinmeto $pinmeto)
    {
        $network_categories = $pinmeto->getNetworkCategories(
            network: 'apple'
        );

        /* ... rest of your code ... */
    }
}
```

## License
This library is licensed under the MIT License. See the [LICENSE.md](LICENSE.md) file for details.

## PinMeTo official API documentation
* The V2 documentation (locations data) is available on [PinMeTo GitHub](https://github.com/PinMeTo/documentation)
* The V3 documentation (locations metrics) is available on [PinMeTo API - Documentation](https://api.pinmeto.com/documentation/v3/)

## Disclaimer
I am not affiliated with PinMeTo, but I am a developer who sees the value of their location services and wanted to create tools to simplify integration for the PHP community.

While this library facilitate integration with PinMeTo's location services API, it is a separate entity maintained and supported by me. Any issues, questions, or inquiries related to these library should be directed to me and not to PinMeTo.

I greatly appreciate the availability of PinMeTo's API, which has enabled me to create this library and enhance the functionality of applications that rely on location-based services. However, the development and maintenance of this library is solely my responsibility (and any contributors to this repository).

Feel free to explore this library here on GitHub, contribute, and make the most of PinMeTo’s powerful location services!

## Standalone PHP library
A standalone PHP library is available also [here](https://github.com/liviu-hariton/pinmeto-php-api).