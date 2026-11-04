<?php
declare(strict_types=1);

namespace QuickDRY\Connectors;

use QuickDRY\Utilities\Strings;
use QuickDRY\Utilities\strongType;

/**
 * Class googleRequest
 */
class GoogleAPI extends strongType
{

    public ?string $gKey = null;
    public ?string $code = null;
    public ?float $Accuracy = null;
    public ?float $latitude = null;
    public ?float $longitude = null;
    public ?string $address = null;
    public ?string $city = null;
    public ?string $zip = null;
    public ?string $country = null;
    public ?string $error = null;
    public ?string $result = null;
    public ?string $state = null;

    /**
     * @param string|null $address
     * @param string|null $city
     * @param string|null $zip
     * @param string|null $country
     *
     * @return GoogleAPI
     */
    public static function GetForAddress(
        ?string $address,
        ?string $city,
        ?string $zip,
        ?string $country = null
    ): GoogleAPI
    {
        $t = new GoogleAPI();

        $t->gKey = GOOGLE_GEOCODEAPIKEY;
        $t->address = $address;
        $t->city = $city;
        $t->zip = $zip;
        $t->country = $country;
        $t->GetRequest();
        return $t;
    }

    /**
     *
     */
    public function GetRequest(): void
    {
        $contextOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ];
        $context = stream_context_create($contextOptions);

        if (strlen($this->gKey) > 1) {
            $q = str_replace(' ', '_', str_replace(' ', '+', urlencode(Strings::KeyboardOnly($this->address))) . ',+' . str_replace(' ', '+', $this->city) . ',+' . str_replace(' ', '+', $this->country) . ',+' . $this->zip);
            if ($d = fopen("https://maps.googleapis.com/maps/api/geocode/json?address=$q&sensor=false&key=" . $this->gKey, 'r', false, $context)) {
                $gcsv = '';
                while ($r = fread($d, 2048)) {
                    $gcsv .= $r;
                }
                fclose($d);
                $this->result = json_encode(json_decode($gcsv, true));
                $res = self::ParseResult($gcsv);
                $this->latitude = $res['latitude'] ?? null;
                $this->longitude = $res['longitude'] ?? null;
                $this->code = $res['code'] ?? null;
                $this->state = $res['state'] ?? null;
                $this->country = $res['country'] ?? null;
                $this->error = $res['error'] ?? null;

                return;
            } else {
                $error = 'NO_CONNECTION';
            }
        } else {
            $error = 'No Google Maps Api Key';
        }
        Exception($error);
    }

    /**
     * @param $result
     * @return array|null
     */
    public static function ParseResult($result): ?array
    {
        if(!json_validate($result)) {
            return null;
        }

        $google = json_decode($result, true)['results'][0] ?? null;

        if(!$google) {
            return null;
        }

        $res = [];
        $res['error'] = '';
        $res['latitude'] = $google['geometry']['location']['lat'];
        $res['longitude'] = $google['geometry']['location']['lng'];
        $res['code'] = $google['place_id'] ?? null;
        foreach($google['address_components'] as $component) {
            if(in_array('administrative_area_level_1', $component['types'])) {
                $res['state'] = $component['short_name'];
            }
            if(in_array('country', $component['types'])) {
                $res['country'] = $component['short_name'];
            }
        }


        if (isset($xml->error_message)) {
            $res['error'] = $xml->error_message;
        }
        if (isset($xml->result[0]) && is_object($xml->result[0])) {
            $res['latitude'] = strip_tags($xml->result[0]->geometry->location->lat->asXML());
            $res['longitude'] = strip_tags($xml->result[0]->geometry->location->lng->asXML());
        }
        return $res;
    }

}
