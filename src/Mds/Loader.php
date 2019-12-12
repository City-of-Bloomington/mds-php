<?php
/**
 * @copyright 2019 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPL, see LICENSE
 */
declare (strict_types=1);

namespace COB\Mds;

class Loader
{
    private $repo;

    public function __construct(RepositoryInterface $repository)
    {
        $this->repo = $repository;
    }

    public function trips(string $dir)
    {
        $files = glob("$dir/*.json");
        foreach ($files as $f) {
            $in   = file_get_contents($f);
            $json = json_decode($in, true);
            foreach ($json['data']['trips'] as $trip) {
                $this->repo->ingestTrip($trip);
            }
        }
    }

    public function status_changes(string $dir)
    {
        $files = glob("$dir/*.json");
        foreach ($files as $f) {
            $in   = file_get_contents($f);
            $json = json_decode($in, true);
            foreach ($json['data']['status_changes'] as $status) {
                $this->repo->ingestStatusChange($status);
            }
        }
    }
}
