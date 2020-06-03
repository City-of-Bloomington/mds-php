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
        $time = (int)$json['last_updated'];
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

        $this->repo->ingestFreeBikeStatus($json['data']['bikes'], $date, $provider);
    }
}
