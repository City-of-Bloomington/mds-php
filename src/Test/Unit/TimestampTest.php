<?php
/**
 * @copyright 2019 City of Bloomington, Indiana
 * @license http://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE
 */
declare (strict_types=1);

use PHPUnit\Framework\TestCase;
use COB\Mds\PostgresRepository;

class TimestampTest extends TestCase
{
    public function timestampData()
    {
        return [
            ['1575982554'    , 1575982554],
            ['1575982554000' , 1575982554],
            ['1575982554.000', 1575982554],
            ['1.575982554E9' , 1575982554],
            ['1575982554321' , 1575982554],
            ['1575982554.543', 1575982554],
            [1575982554    , 1575982554],
            [1575982554000 , 1575982554],
            [1575982554.000, 1575982554],
            [1575982554321 , 1575982554]
        ];
    }

	/**
	 * @dataProvider timestampData
	 */
    public function testTimestampParser($input, int $output)
    {
        $this->assertEquals($output, PostgresRepository::parseTimestamp($input));
    }
}
