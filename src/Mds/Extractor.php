<?php
/**
 * @copyright 2019 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPL, see LICENSE
 */
declare (strict_types=1);

namespace COB\Mds;

use GuzzleHttp\Psr7;

class Extractor
{
    const HOUR_FORMAT = 'Y-m-d\TH';

    private $provider;
    private $token;
    private $api_version;
    private $endpoint;

    public function __construct(array $config)
    {
        $this->provider    = $config['provider'   ];
        $this->token       = $config['token'      ];
        $this->api_version = $config['api_version'];
        $this->endpoint    = $config['endpoint'   ];

        if (!$this->provider   ) { throw new \Exception('missingProvider'  ); }
        if (!$this->token      ) { throw new \Exception('missingToken'     ); }
        if (!$this->api_version) { throw new \Exception('missingApiVersion'); }
        if (!$this->endpoint   ) { throw new \Exception('missingEndpiont'  ); }
    }

    /**
     * Return trip data from the MDS provider
     *
     * Trip data is available on a per-hour basis.  You must specify the hour
     * of the day when making a request.
     *
     * @see https://github.com/openmobilityfoundation/mobility-data-specification/tree/dev/provider#trips
     */
    public function trips(\DateTime $start, \DateTime $end, string $outputDirectory)
    {
        $params = http_build_query([
            'start_time' => $start->format('U'),
              'end_time' =>   $end->format('U')
        ], '', '&');
        $url    = "{$this->endpoint}/trips?$params";
        $this->downloadData($url, $outputDirectory);
    }

    /**
     * Return status change data from the MDS provider
     */
    public function status_changes(\DateTime $hour)
    {
        $client   = new \GuzzleHttp\Client();
        $request  = new Psr7\Request('GET',
                                     $this->endpoint.'/status_changes?end_time='.self::end_time($hour),
                                     $this->headers());
    }

    private function downloadData(string $url, string $dir)
    {
        $json['links']['next'] = $url;

        while (!empty($json['links']['next'])) {
            echo $json['links']['next']."\n";

            $out   = $this->query($json['links']['next']);
            $json  = json_decode($out, true);
            if ($json) {
                self::saveUrlResponseToFile($json['links']['next'], $out, $dir);
            }
        }
    }

    private static function saveUrlResponseToFile(string $url, string $response, string $dir)
    {
        $u      = parse_url($url);
        $params = [];
        parse_str($u['query'], $params);
        $date   = new \DateTime();
        $date->setTimestamp((int)$params['start_time']);
        $start  = $date->format('c');
        $file   = "$dir/$start.json";
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
            'APP-Version'   => $this->api_version,
            'Authorization' => "{$this->provider} {$this->token}",
            'Content-Type'  => 'application/json'
        ];
    }
}
