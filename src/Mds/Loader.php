<?php
/**
 * @copyright 2019-2022 City of Bloomington, Indiana
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

    /**
     * Load a directory of JSON files into the database
     *
     * @param  string $dir  Directory of json files to process
     * @return array        Invalid data from the json files
     */
    public function status_changes(string $dir): array
    {
        $files  = glob("$dir/*.json");
        $errors = [];
        foreach ($files as $f) {
            $in   = file_get_contents($f);
            $json = json_decode($in, true);
            foreach ($json['data']['status_changes'] as $status) {
                if (self::status_change_is_valid($status)) {
                    $this->repo->ingestStatusChange($status);
                }
                else {
                    $errors[$f][] = $status;
                }
            }
        }
        return $errors;
    }

    private static function status_change_is_valid(array $row): bool
    {
        $required = [
            'provider_id',
            'provider_name',
            'device_id',
            'vehicle_id',
            'propulsion_types',
            'vehicle_state',
            'event_types',
            'event_location'
        ];
        foreach ($required as $f) {
            if (empty($row[$f])) { return false; }
        }
        return true;
    }
}
