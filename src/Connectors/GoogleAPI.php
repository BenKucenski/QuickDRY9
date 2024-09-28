<?php

namespace Bkucenski\Quickdry\Connectors;

use Exception;
use Bkucenski\Quickdry\Utilities\Debug;
use Bkucenski\Quickdry\Utilities\Strings;
use SimpleXMLElement;

/**
 * Class googleRequest
 */
class GoogleAPI
{

    public ?string $gKey;
    public ?string $code;
    public ?float $Accuracy;
    public ?float $latitude;
    public ?float $longitude;
    public ?string $address;
    public ?string $city;
    public ?string $zip;
    public ?string $country;
    public ?string $error;
    public ?string $result;

    /**
     * @param string $address
     * @param string $city
     * @param string $zip
     * @param string $country
     *
     * @return GoogleAPI
     */
    public static function GetForAddress(
        string $address,
        string $city,
        string $zip,
        string $country = ''): GoogleAPI
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
            if ($d = fopen("https://maps.googleapis.com/maps/api/geocode/xml?address=$q&sensor=false&key=" . $this->gKey, 'r', null, $context)) {
                $gcsv = '';
                while ($r = fread($d, 2048)) {
                    $gcsv .= $r;
                }
                fclose($d);
                $this->result = $gcsv;
                $res = self::ParseResult($gcsv);
                $this->latitude = $res['latitude'];
                $this->longitude = $res['longitude'];
                $this->error = $res['error'];

                return;
            } else {
                $error = 'NO_CONNECTION';
            }
        } else {
            $error = 'No Google Maps Api Key';
        }
        Debug::Halt($error);
    }

    /**
     * @param $result
     * @return array
     */
    public static function ParseResult($result): array
    {
        $res = [];
        $res['error'] = '';
        $res['latitude'] = 0;
        $res['longitude'] = 0;

        if (!$result) {
            return $res;
        }

        try {
            $xml = new SimpleXMLElement($result);
        } catch (Exception $ex) {
            return $res;
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
