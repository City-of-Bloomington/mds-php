<?php
/**
 * @copyright 2020 City of Bloomington, Indiana
 * @license http://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);
namespace COB\GBFS;

class PostgresRepository implements RepositoryInterface
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function ingestFreeBikeStatus(array $data, \DateTime $last_updated, string $provider)
    {
        $cols   = implode(',', self::$GBFS_COLUMNS);
        $binds  = implode(',', self::paramNames(self::$GBFS_COLUMNS));

        $sql    = "insert into free_bike_status ($cols) values($binds)
                   on conflict(provider_name, last_updated, bike_id) do nothing";
        $insert = $this->pdo->prepare($sql);
        foreach ($data as $row) {
            $insert->execute(self::boundParams($row, $last_updated, $provider));
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
     */
    private static function boundParams(array $row, \DateTime $last_updated, string $provider): array
    {
        $params = [
            ':provider_name' => $provider,
            ':last_updated'  => $last_updated->format('c')
        ];
        foreach ($row as $k=>$v) { $params[":$k"] = $v; }
        return $params;
    }
}
