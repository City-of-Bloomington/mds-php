<?php
/**
 * @copyright 2020 City of Bloomington, Indiana
 * @license http://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);
namespace COB\GBFS;

use COB\Constants;

class PostgresRepository implements RepositoryInterface
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @see https://github.com/NABSA/gbfs/blob/v2.0-RC/gbfs.md#free_bike_statusjson
     * @param array     $bike          Data for a single bike record in the free_bike_status response
     * @param \DateTime $last_updated  The last_updated value from the free_bike_status response
     * @param string    $provider      Name of the provider
     */
    public function ingestFreeBikeStatus(array $bikes, \DateTime $last_updated, string $provider)
    {
        $cols   = implode(',', self::$GBFS_COLUMNS);
        $binds  = implode(',', self::paramNames(self::$GBFS_COLUMNS));

        $sql    = "insert into free_bike_status ($cols) values($binds)
                   on conflict(provider_name, last_updated, bike_id) do nothing";
        $insert = $this->pdo->prepare($sql);
        foreach ($bikes as $bike) {
            $insert->execute(self::boundParams($bike, $last_updated, $provider));
        }
    }

    private static $GBFS_COLUMNS = [
        'provider_name', 'last_updated', 'bike_id',
        'lat', 'lon', 'is_reserved', 'is_disabled', 'vehicle_type'
    ];

    /**
     * Creates an array of named parameters for use in SQL queries
     */
    private static function paramNames(array $cols): array
    {
        $binds = [];
        foreach ($cols as $c) { $binds[] = ":$c"; }
        return $binds;
    }

    /**
     * Binds data to named parameters
     *
     * @see https://github.com/NABSA/gbfs/blob/v2.0-RC/gbfs.md#free_bike_statusjson
     * @param array     $bike          Data for a single bike record in the free_bike_status response
     * @param \DateTime $last_updated  The last_updated value from the free_bike_status response
     * @param string    $provider      Name of the provider
     */
    private static function boundParams(array $bike, \DateTime $last_updated, string $provider): array
    {
        $params = [
            ':provider_name' => $provider,
            ':last_updated'  => $last_updated->format('c')
        ];
        foreach (self::providerFieldMap($provider) as $jsonField => $dbColumn) {
            $params[":$dbColumn"] = $bike[$jsonField];
        }
        return $params;
    }

    /**
     * Returns an array mapping the json field names to database column names
     * @param string $provider Name of the provider
     */
    private static function providerFieldMap(string $provider): array
    {
        switch ($provider) {
            case Constants::PROVIDER_VEORIDE:
                // JSON fieldname => Database column name
                return [
                    'bikeId'      => 'bike_id',
                    'lat'         => 'lat',
                    'lon'         => 'lon',
                    'isReserved'  => 'is_reserved',
                    'isDisabled'  => 'is_disabled',
                    'vehicleType' => 'vehicle_type'
                ];
            break;

            default:
                // JSON fieldname => Database column name
                return [
                    'bike_id'     => 'bike_id',
                    'lat'         => 'lat',
                    'long'        => 'long',
                    'is_reserved' => 'is_reserved',
                    'is_disabled' => 'is_disabled',
                    'vehicle_type'=> 'vehicle_type'
                ];
        }
    }
}
