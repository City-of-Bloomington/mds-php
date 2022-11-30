<?php
/**
 * @copyright 2019-2022 City of Bloomington, Indiana
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
        $params  = self::boundParametersForTrip($trip);
        $cols    = implode(',', self::$TRIP_COLUMNS);
        $binds   = implode(',', array_keys($params));

        $sql     = "insert into trips ($cols) values($binds)
                    on conflict (trip_id) do nothing";

        $trip_id = $params[':trip_id'];
        $start   = $params[':start_time'];
        $query   = $this->pdo->prepare($sql);
        $query->execute($params);
    }

    public function ingestStatusChange(array $status)
    {
        $params = self::boundParametersForStatus($status);
        $cols   = implode(',', self::$STATUS_COLUMNS);
        $binds  = implode(',', array_keys($params));
        $sql    = "insert into status_changes ($cols) values($binds)
                   on conflict (device_id, event_time) do nothing";
        $query  = $this->pdo->prepare($sql);
        $query->execute($params);
    }

    /**
     * Creates a unix timestamp from various timestamp representations
     *
     * MDS providers are inconsistent in formatting timestamps; even within
     * a single response.  Timestamps are represented, variously as:
     * - int     seconds                  1575982554
     * - int     milliseconds             1575982554000
     * - float   milliseconds             1575982554.000
     * - string  scientific notation      1.575982554E9
     *
     * @return int Unix timestamp in seconds
     */
    public static function parseTimestamp($timestamp): int
    {
        $timestamp = (string)$timestamp;
        if     (preg_match('/^\d{10}$/',     $timestamp)) { return (int)$timestamp;        }
        elseif (preg_match('/^\d{13}$/',     $timestamp)) { return (int)($timestamp/1000); }
        elseif (preg_match('/^\d{10}\.\d+/', $timestamp)) { return (int)$timestamp;        }
        elseif (preg_match('/\d\.\d+E\d+/',  $timestamp)) { return (int)$timestamp;        }
        else {
            throw new \Exception('invalidTimestamp');
        }
    }

    private static $TRIP_COLUMNS = [
        'provider_id', 'provider_name', 'device_id', 'vehicle_id', 'vehicle_type', 'propulsion_types',
        'trip_id', 'trip_duration', 'trip_distance', 'route', 'accuracy',
        'start_time', 'end_time', 'publication_time', 'parking_verification_url',
        'standard_cost', 'actual_cost'
    ];

    private static function boundParametersForTrip(array $trip): array
    {
        $params = [];
        foreach (self::$TRIP_COLUMNS as $f) {
            if (isset($trip[$f])) {
                switch ($f) {
                    case 'route':
                    case 'propulsion_types':
                        $params[":$f"] = json_encode($trip[$f]);
                    break;

                    case 'start_time':
                    case 'end_time':
                    case 'publication_time':
                        $params[":$f"] = date('c', self::parseTimestamp($trip[$f]));
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
        'provider_id', 'provider_name', 'device_id', 'vehicle_id', 'vehicle_type', 'propulsion_types',
        'vehicle_state', 'event_types', 'event_time', 'publication_time',
        'event_location', 'battery_pct', 'trip_id', 'associated_ticket'
    ];
    private static function boundParametersForStatus(array $status): array
    {
        $params = [];

        foreach (self::$STATUS_COLUMNS as $f) {
            if (!empty($status[$f])) {
                switch ($f) {
                    case 'event_time':
                    case 'publication_time':
                        $params[":$f"] = date('c', self::parseTimestamp($status[$f]));
                    break;

                    case 'event_types':
                    case 'event_location':
                    case 'propulsion_types':
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
