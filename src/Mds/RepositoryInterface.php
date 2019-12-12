<?php
/**
 * @copyright 2019 City of Bloomington, Indiana
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU/GPL, see LICENSE
 */
declare (strict_types=1);

namespace COB\Mds;

interface RepositoryInterface
{
    public function ingestTrip        (array $trip  );
    public function ingestStatusChange(array $status);
}
