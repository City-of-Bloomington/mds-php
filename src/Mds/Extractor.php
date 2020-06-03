<?php
/**
 * @copyright 2019-2020 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPL, see LICENSE
 */
declare (strict_types=1);

namespace COB\Mds;

use GuzzleHttp\Psr7;

class Extractor
{
    private $provider;
    private $token;
    private $endpoint;

    public function __construct(string $provider, string $endpoint, string $token)
    {
        $this->provider    = $provider;
        $this->token       = $token;
        $this->endpoint    = $endpoint;
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
            'min_end_time' => $start->format('U').'000',
            'max_end_time' => $end  ->format('U').'000'
        ], '', '&');
        $url    = "{$this->endpoint}/trips?$params";
        echo "$url\n";
        $this->downloadData($url, $outputDirectory, 'trips');
    }

    /**
     * Return status change data from the MDS provider
     */
    public function status_changes(\DateTime $start, \DateTime $end, string $outputDirectory)
    {
        $params = http_build_query([
            'start_time' => $start->format('U').'000',
              'end_time' => $end  ->format('U').'000'
        ], '', '&');
        $url    = "{$this->endpoint}/status_changes?$params";
        $this->downloadData($url, $outputDirectory, 'status_changes');
    }

    private function downloadData(string $url, string $dir, string $type)
    {
        while ($url) {
            $out   = $this->query($url);
            $json  = json_decode($out, true);
            if ($json) {
                $datetime = self::extractTimeFromQuery($url);

                #if (!empty($json['data'][$type])) {
                    self::saveUrlResponseToFile($datetime, $out, $dir);
                #}

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
            'Content-Type'  => 'application/vnd.mds.provider+json;version=0.3',
            'Accept'        => 'application/vnd.mds.provider+json;version=0.3'
        ];
    }

    private static function extractTimeFromQuery(string $url): \DateTime
    {
        $u      = parse_url($url);
        $params = [];
        parse_str($u['query'], $params);
        $time   = isset( $params['min_end_time'] )
                  ? (int)$params['min_end_time']
                  : (int)$params[    'end_time'];
        $date = new \DateTime();
        switch (strlen((string)$time)) {
            case 13:
                $date->setTimestamp((int)round($time/1000));
            break;
            case 10:
                $date->setTimestamp($time);
            break;
            default:
                throw new \Exception('invalidTimestamp');
        }
        return $date;
    }
}
