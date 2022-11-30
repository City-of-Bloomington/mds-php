<?php
/**
 * @copyright 2019-2022 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPL, see LICENSE
 */
declare (strict_types=1);

namespace COB\Mds;

use GuzzleHttp\Psr7;

class Extractor
{
    const FORMAT_HOUR = 'Y-m-d\TH';

    private $provider;
    private $token;
    private $endpoint;
    private $version;

    public function __construct(string $provider, string $endpoint, string $token, string $version)
    {
        $this->provider    = $provider;
        $this->token       = $token;
        $this->endpoint    = $endpoint;
        $this->version     = $version;
    }

    /**
     * Download trips json files from an MDS provider
     *
     * @see https://github.com/openmobilityfoundation/mobility-data-specification/blob/main/provider/README.md#trips
     */
    public function trips(\DateTime $start, \DateTime $end, string $outputDirectory)
    {
        $hour   = new \DateInterval('PT1H');
        $s      = clone($start);
        $s->setTimezone(new \DateTimeZone('UTC'));
        while ($s < $end) {
            $params = http_build_query([
                'end_time' => $s->format(self::FORMAT_HOUR)
            ], '', '&');
            $url    = "{$this->endpoint}/trips?$params";
            $this->downloadData($url, $outputDirectory, 'trips', $s);
            $s->add($hour);
        }
    }

    /**
     * Download status_changes json files from an MDS provider
     *
     * @see https://github.com/openmobilityfoundation/mobility-data-specification/blob/main/provider/README.md#status-changes
     */
    public function status_changes(\DateTime $start, \DateTime $end, string $outputDirectory)
    {
        $hour   = new \DateInterval('PT1H');
        $s      = clone($start);
        $s->setTimezone(new \DateTimeZone('UTC'));
        while ($s < $end) {
            $params = http_build_query([
                'event_time' => $s->format(self::FORMAT_HOUR)
            ], '', '&');
            $url    = "{$this->endpoint}/status_changes?$params";
            $this->downloadData($url, $outputDirectory, 'status_changes', $start);
            $s->add($hour);
        }
    }

    private function downloadData(string $url, string $dir, string $type, \DateTime $datetime)
    {
        while ($url) {
            $out   = $this->query($url);
            $json  = json_decode($out, true);
            if ($json) {
                if (!empty($json['data'][$type])) {
                    self::saveUrlResponseToFile($datetime, $out, $dir);
                }

                $url = !empty($json['links']['next']) ? $json['links']['next'] : null;
            }
            else {
                $url = null;
            }
        }
    }

    private static function saveUrlResponseToFile(\DateTime $datetime, string $response, string $dir)
    {
        $start     = $datetime->format('c');
        $existing  = glob("$dir/$start*.json");
        $count     = count($existing);
        $file      = "$dir/$start-$count.json";
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
            'Authorization' => "Bearer {$this->token}",
            'Accept'        => 'application/vnd.mds+json;version='.$this->version
        ];
    }
}
