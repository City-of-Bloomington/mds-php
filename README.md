# MDS ETL Library for PHP

[The Mobility Data Specification](https://github.com/openmobilityfoundation/mobility-data-specification) (MDS), a project of the [Open Mobility Foundation](http://www.openmobilityfoundation.org) (OMF), is a set of Application Programming Interfaces (APIs) focused on dockless e-scooters, bicycles and carshare.

## Usage

```php
<?php
declare (strict_types=1);
use COB\Mds\Extractor;
use COB\Mds\Loader;
use COB\Mds\PostgresRepository;

include './vendor/autoload.php';
$config = include './config.php';

$db     = $config['database'];
$pdo    = new \PDO("$db[driver]:dbname=$db[dbname];host=$db[host]", $db['username'], $db['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("set search_path=$db[schema]");

$repo    = new PostgresRepository($pdo);
$extract = new Extractor($config['providers']['Bird']);
$load    = new Loader($repo);

$day     = new \DateInterval('P1D');
$start   = new \DateTime('2019-11-21');
$end     = clone($start);
$end     = $end->add($day);

$extract->trips         ($start, $end, __DIR__.'/data/trips' );
$extract->status_changes($start, $end, __DIR__.'/data/status');

$load->trips         (__DIR__.'/data/trips');
$load->status_changes(__DIR__.'/data/status');
```
