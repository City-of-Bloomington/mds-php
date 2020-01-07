# MDS ETL Library for PHP

[The Mobility Data Specification](https://github.com/openmobilityfoundation/mobility-data-specification) (MDS), a project of the [Open Mobility Foundation](http://www.openmobilityfoundation.org) (OMF), is a set of Application Programming Interfaces (APIs) focused on dockless e-scooters, bicycles and carshare.

## Install using [composer](https://getcomposer.org/)
```json
{
    "repositories": [{
        "type": "vcs",
        "url": "https://github.com/City-of-Bloomington/blossom-lib"
    }],
    "require": {
        "city-of-bloomington/mds": "dev-master"
    }
}
```

```
composer update
```

## Usage

```php
<?php
declare (strict_types=1);
use COB\Mds\Extractor;
use COB\Mds\Loader;
use COB\Mds\PostgresRepository;

include './vendor/autoload.php';
$config  = include './config.php';

$provider = 'Bird';
$day      = new \DateInterval('P1D');
$hour     = new \DateInterval('PT1H');
$start    = new \DateTime('2019-07-26');
$minDate  = new \DateTime('2018-09-01');

#----------------------------------------------------------
# Extract
#----------------------------------------------------------
$extract  = new Extractor($config['providers'][$provider]);

while ($start > $minDate) {
    $end     = clone($start);
    $end->add($day);

    $ymd     = $start->format('Y/m/d');
    $trips   = __DIR__."/data/$provider/trips/$ymd";
    $status  = __DIR__."/data/$provider/status/$ymd";
    if (!is_dir($trips )) { mkdir($trips,  0766, true); }
    if (!is_dir($status)) { mkdir($status, 0766, true); }

    $extract->trips         ($start, $end, $trips );
    $extract->status_changes($start, $end, $status);

    $start->sub($day);
}

#----------------------------------------------------------
# Load
#----------------------------------------------------------
$db     = $config['database'];
$pdo    = new \PDO("$db[driver]:dbname=$db[dbname];host=$db[host]", $db['username'], $db['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("set search_path=$db[schema]");

$repo   = new PostgresRepository($pdo);
$load   = new Loader($repo);

while ($start > $minDate) {

    $ymd     = $start->format('Y/m/d');
    $trips   = __DIR__."/data/$provider/trips/$ymd";
    $status  = __DIR__."/data/$provider/status/$ymd";

    echo "Loading $trips\n";
    $load->trips         ($trips );
    echo "Loading $status\n";
    $load->status_changes($status);

    $start->sub($day);
}
```
