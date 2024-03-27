<?php

namespace LHDev\PinmetoLaravel;

use Exception;
use stdClass;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class Pinmeto
{
    const _ENDPOINT_LIVE = 'https://api.pinmeto.com';
    const _ENDPOINT_TEST = 'https://api.test.pinmeto.com';

    const _API_VERSION_LOCATIONS = '2';
    const _API_VERSION_METRICS = '3';

    private string $token;
    private string $endpoint;

    /**
     * The PinMeTo `App ID`
     *
     * @var string
     */
    private string $app_id;

    /**
     * The PinMeTo `App Secret`
     *
     * @var string
     */
    private string $app_secret;

    /**
     * The PinMeTo `Account ID`
     *
     * @var string
     */
    private string $account_id;

    /**
     * Constructor
     * Initialize it with your `Account ID`, `App ID` and `App Secret` values. You can get them / generate them inside
     * your PinMeTo Account Settings - https://places.pinmeto.com/account-settings/
     *
     * @param array $config_data Should contain the keys:<br>
     * 'app_id' - the PinMeTo `App ID`<br>
     * 'app_secret' - the PinMeTo `App Secret`<br>
     * 'account_id' - the PinMeTo `Account ID`<br>
     * 'mode' - the library working mode: `live` or `test` (defaults to `test`)
     *
     * @throws Exception
     */
    public function __construct(array $config_data)
    {
        $this->saveConfig($config_data);

        $this->token = $this->authenticate();
    }

    /**
     * Validate configuration data
     *
     * @param array $config_data
     * @throws Exception
     */
    private function validateConfig(array $config_data): void
    {
        $required_credentials = ['app_id', 'app_secret', 'account_id'];

        // Check if the required credentials are provided
        foreach($required_credentials as $credential) {
            if(empty($config_data[$credential])) {
                throw new Exception("You need to provide the PinMeTo API credentials [`".implode('`, `', $required_credentials)."`]");
            }
        }

        // Check if the working mode is set
        if(empty($config_data['mode']) || !in_array($config_data['mode'], ['live', 'test'])) {
            throw new Exception("You need to provide the library working mode: `live` or `test`");
        }
    }

    /**
     * @throws Exception
     */
    private function saveConfig($config_data): void
    {
        $this->validateConfig($config_data);

        $this->app_id = $config_data['app_id'];
        $this->app_secret = $config_data['app_secret'];
        $this->account_id = $config_data['account_id'];

        $this->endpoint = $this->setEndpoint($config_data['mode']);
    }

    /**
     * Set the API endpoint based on the library's working mode. Possible values: `live` or `test`
     *
     * @var string $working_mode
     * @return string
     */
    private function setEndpoint(string $working_mode): string
    {
        return $working_mode === 'live' ? self::_ENDPOINT_LIVE : self::_ENDPOINT_TEST;
    }

    /**
     * Get the API Access Token and cache it in the current Session or regenerate it if it has expired
     *
     * @return string|null
     * @throws Exception
     */
    private function authenticate(): string|null
    {
        // Check if the token is set or it's not expired
        if(!Cache::has('_pinmeto_token')) {
            $result = json_decode($this->connect('oauth/token', ['grant_type' => 'client_credentials'], 'POST'));

            if(empty($result->access_token)) {
                throw new Exception("Could not retrieve the token's value");
            }

            Cache::put('_pinmeto_token', $result->access_token, $result->expires_in);
        }

        return Cache::get('_pinmeto_token');
    }

    /**
     * Perform the actual connection and get the requested data, if available
     *
     * @param string $call The API method to call
     * @param array $parameters Any data that should be transmitted
     * @param string $method The HTTP method
     * @return bool|string|stdClass
     * @throws Exception
     */
    private function connect(string $call, array $parameters = array(), string $method = 'GET'): bool|string|stdClass
    {
        $url = $this->endpoint;

        // Handle authorization headers
        if(str_contains($call, "token")) {
            $url .= '/'.$call;

            $headers = [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic ' . base64_encode($this->app_id.':'.$this->app_secret),
            ];
        } else {
            $headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$this->token,
            ];
        }

        // Construct the URL based on API version and call type
        if(!str_contains($call, "token") && !str_contains($call, "google") && !str_contains($call, "facebook")) {
            $url .= '/v'.self::_API_VERSION_LOCATIONS.'/'.$this->account_id.'/'.$call;
        } elseif(str_contains($call, "google") || str_contains($call, "facebook")) {
            $url .= '/listings/v'.self::_API_VERSION_METRICS.'/'.$this->account_id.'/'.$call;
        }

        // Add query parameters for GET requests
        if ($method === 'GET' && !empty($parameters)) {
            $url .= '?'.http_build_query($parameters);
        }

        // Make the HTTP request
        $response = Http::withHeaders($headers)->{strtolower($method)}($url, $parameters);

        return $response->body();
    }

    /**
     * Get all available locations
     *
     * @param array (optional) $parameters Possible keys:<br>
     * `pagesize` - Number of locations that should be returned (default: 100, maximum: 250)<br>
     * `next` - Id of starting point to next page<br>
     * `before` - Id of starting point to previous page
     *
     * @return bool|string|stdClass
     * @throws Exception
     */
    public function getLocations(array $parameters = []): bool|string|stdClass
    {
        return $this->connect('locations', $parameters);
    }

    /**
     * Get the details for a specific location
     *
     * @param string $store_id
     * @return bool|stdClass|string
     * @throws Exception
     */
    public function getLocation(string $store_id): bool|string|stdClass
    {
        return $this->connect('locations/'.$store_id);
    }

    /**
     * Create a new location
     *
     * @param array $parameters
     * @param bool $upsert
     * @return bool|string|stdClass
     * @throws Exception
     */
    public function createLocation(array $parameters = [], bool $upsert = false): bool|string|stdClass
    {
        return $this->connect('locations/'.($upsert ? '?upsert=true' : ''), $parameters, 'POST');
    }

    /**
     * Update an existing location
     *
     * @param string $store_id
     * @param array $parameters
     * @return bool|string|stdClass
     * @throws Exception
     */
    public function updateLocation(string $store_id, array $parameters = []): bool|string|stdClass
    {
        return $this->connect('locations/'.$store_id, $parameters, 'PUT');
    }

    /**
     * Get the metrics data for all locations or for a specific location
     *
     * @param string $source The data source (`google` or `facebook`))
     * @param string $from_date The start date of the calendaristic interval, in the YYYY-MM-DD format
     * @param string $to_date The end date of the calendaristic interval, in the YYYY-MM-DD format
     * @param array $fields The specific fields values that should be returned (if none provided, it returns everything)).
     * All available fields are described here https://api.pinmeto.com/documentation/v3/
     *
     * @param string $store_id (optional) The specific Store ID
     * @return bool|stdClass|string
     * @throws Exception
     */
    public function getMetrics(string $source, string $from_date, string $to_date, array $fields = [], string $store_id = ''): bool|string|stdClass
    {
        if(!in_array($source, ['google', 'facebook'])) {
            throw new Exception("You need to provide a valid source - `google` or `facebook`");
        }

        $parameters = [
            'from' => $from_date,
            'to' => $to_date,
        ];

        if(count($fields) > 0) {
            $parameters['fields'] = implode(",", $fields);
        }

        return $this->connect('insights/'.$source.'/'.($store_id !== '' ? $store_id : ''), $parameters);
    }

    /**
     * Get the Google keywords for all locations or for a specific location
     *
     * @param string $from_date The start date of the calendaristic interval, in the YYYY-MM format
     * @param string $to_date The end date of the calendaristic interval, in the YYYY-MM format
     * @param string $store_id (optional) The specific Store ID
     * @return bool|string|stdClass
     * @throws Exception
     */
    public function getKeywords(string $from_date, string $to_date, string $store_id = ''): bool|string|stdClass
    {
        $parameters = [
            'from' => $from_date,
            'to' => $to_date,
        ];

        return $this->connect('insights/google-keywords/'.($store_id !== '' ? $store_id : ''), $parameters);
    }

    /**
     * Get the ratings data for all locations or for a specific location
     *
     * @param string $source The data source (`google` or `facebook`))
     * @param string $from_date The start date of the calendaristic interval, in the YYYY-MM format
     * @param string $to_date The end date of the calendaristic interval, in the YYYY-MM format
     * @param string $store_id (optional) The specific Store ID
     * @return bool|string|stdClass
     * @throws Exception
     */
    public function getRatings(string $source, string $from_date, string $to_date, string $store_id = ''): bool|string|stdClass
    {
        if(!in_array($source, ['google', 'facebook'])) {
            throw new Exception("You need to provide a valid source - `google` or `facebook`");
        }

        $parameters = [
            'from' => $from_date,
            'to' => $to_date,
        ];

        return $this->connect('ratings/'.$source.'/'.($store_id !== '' ? $store_id : ''), $parameters);
    }
}
