<?php
/**
 * @copyright 2020 City of Bloomington, Indiana
 * @license http://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);

namespace COB\GBFS;

class Loader
{
    private $repo;

    public function __construct(RepositoryInterface $repository)
    {
        $this->repo = $repository;
    }

    public function free_bike_status(string $file, string $provider)
    {
        $in   = file_get_contents($file);
        $json = json_decode($in, true);
        $df   = self::dateFieldForProvider($provider);
        $date = \DateTime::createFromFormat('U', (string)$json[$df]);

        $this->repo->ingestFreeBikeStatus($json['data']['bikes'], $date, $provider);
    }

    private static function dateFieldForProvider(string $provider): string
    {
        switch ($provider) {
            case 'VeoRide':
                return 'lastUpdated';
            break;

            default:
                return 'last_updated';
        }
    }
}
