<?php
/**
 * @copyright 2019 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPL, see LICENSE
 */
declare (strict_types=1);

namespace COB\Mds;

class PostgresRepository implements RepositoryInterface
{
    const DATETIME_FORMAT = 'Y-m-d ';
    private $pdo;


    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function ingestTrip(array $trip)
    {
        $params = self::boundParametersForTrip($trip);
        $cols   = implode(',', self::$TRIP_COLUMNS);
        $binds  = implode(',', array_keys($params));

        $sql    = "insert into trips ($cols) values($binds)
                   on conflict (trip_id) do nothing";
        $query  = $this->pdo->prepare($sql);
        $query->execute($params);
    }

    public function ingestStatusChange(array $status)
    {
        if (!empty($status['associated_trips']) && empty($status['associated_trip'])) {
            $status['associated_trip'] = $status['associated_trips'][0];
        }

        $params = self::boundParametersForStatus($status);
        $cols   = implode(',', self::$STATUS_COLUMNS);
        $binds  = implode(',', array_keys($params));
        $sql    = "insert into status_changes ($cols) values($binds)
                   on conflict (device_id, event_time) do nothing";
        $query  = $this->pdo->prepare($sql);
        $query->execute($params);
    }

    private static $TRIP_COLUMNS = [
        'provider_id', 'provider_name', 'device_id', 'vehicle_id', 'vehicle_type', 'propulsion_type',
        'trip_id', 'trip_duration', 'trip_distance', 'route', 'accuracy',
        'start_time', 'end_time', 'publication_time', 'parking_verification_url',
        'standard_cost', 'actual_cost', 'currency'
    ];

    private static function boundParametersForTrip(array $trip): array
    {
        $params = [];
        foreach (self::$TRIP_COLUMNS as $f) {
            if (!empty($trip[$f])) {
                switch ($f) {
                    case 'route':
                    case 'propulsion_type':
                        $params[":$f"] = json_encode($trip[$f]);
                    break;

                    case 'start_time':
                    case 'end_time':
                    case 'publication_time':
                        $params[":$f"] = date('c', (int)$trip[$f]);
                    break;

                    default:
                        $params[":$f"] = $trip[$f];
                }
            }
            else {
                $params[":$f"] = null;
            }
        }
        return $params;
    }

    private static $STATUS_COLUMNS = [
        'provider_id', 'provider_name', 'device_id', 'vehicle_id', 'vehicle_type', 'propulsion_type',
        'event_type', 'event_type_reason', 'event_time', 'publication_time',
        'event_location', 'battery_pct', 'associated_trip', 'associated_ticket'
    ];
    private static function boundParametersForStatus(array $status): array
    {
        $params = [];
        foreach (self::$STATUS_COLUMNS as $f) {
            if (!empty($status[$f])) {
                switch ($f) {
                    case 'event_time':
                    case 'publication_time':
                        $params[":$f"] = date('c', (int)$status[$f]);
                    break;

                    case 'event_location':
                    case 'propulsion_type':
                        $params[":$f"] = json_encode($status[$f]);
                    break;

                    default:
                        $params[":$f"] = $status[$f];
                }
            }
            else {
                $params[":$f"] = null;
            }
        }
        return $params;
    }
}
