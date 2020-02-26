<?php
/**
 * @copyright 2020 City of Bloomington, Indiana
 * @license http://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);

namespace COB\GBFS;

interface RepositoryInterface
{
    public function ingestFreeBikeStatus(array $data, \DateTime $last_updated, string $provider);
}
