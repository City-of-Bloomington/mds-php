# MDS ETL Library for PHP

[The Mobility Data Specification](https://github.com/openmobilityfoundation/mobility-data-specification) (MDS), a project of the [Open Mobility Foundation](http://www.openmobilityfoundation.org) (OMF), is a set of Application Programming Interfaces (APIs) focused on dockless e-scooters, bicycles and carshare.

## Usage

```php
<?php
declare (strict_types=1);
use COB\Mds\Extractor;

include './vendor/autoload.php';

$token = 'ASDKLJASKLDJASLKDJ';
$client = new Extractor([
    'provider'    => 'Bird',
    'token'       => $token,
    'api_version' => '3.0.0',
    'endpoint'    => 'https://mds.bird.co'
]);


$day   = new \DateInterval('P1D');
$start = new \DateTime('2019-11-21');
$end   = clone($start);
$end   = $end->add($day);

$client->trips         ($start, $end, __DIR__.'/data/trips' );
$client->status_changes($start, $end, __DIR__.'/data/status');
```
