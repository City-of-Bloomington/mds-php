<?php
/**
 * @copyright 2020 City of Bloomington, Indiana
 * @license http://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);

namespace COB\GBFS;

use GuzzleHttp\Psr7;

class Extractor
{
    private $provider;
    private $token;
    private $endpoint;

    public function __construct(array $config)
    {
        $this->provider    = $config['provider'];
        $this->token       = $config['token'   ];
        $this->endpoint    = $config['endpoint'];
    }

    public function free_bike_status(string $outputDirectory)
    {
        $url = "{$this->endpoint}/free_bike_status.json";
        $this->downloadData($url, $outputDirectory);
    }

    private function downloadData(string $url, string $dir)
    {
        $response = $this->query($url);
        $json     = json_decode($response, true);
        if ($json) {
            self::saveResponseToFile(json_encode($json, JSON_PRETTY_PRINT), $dir);
        }
    }

    private static function saveResponseToFile(string $response, string $dir)
    {
        $file = "$dir/free_bike_status.json";
        file_put_contents($file, $response);
    }

    private function query($url)
    {
        $client   = new \GuzzleHttp\Client();
        $request  = new Psr7\Request('GET', $url, $this->headers());
        $response = $client->send($request);
        $body     = $response->getBody()->__toString();
        return $body;
    }

    private function headers(): array
    {
        return [
            'Authorization' => "Bearer {$this->token}"
        ];
    }
}
