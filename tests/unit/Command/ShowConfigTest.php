<?php
/**
 * @author Artur Neumann <artur@jankaritech.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\User_LDAP;

use OCA\User_LDAP\Command\ShowConfig;
use OCA\User_LDAP\Config\Server;
use OCA\User_LDAP\Config\ServerMapper;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;

/**
 * Class CommandTest
 *
 */
class ShowConfigTest extends TestCase {
	/** @var CommandTester */
	private $commandTester;
	/** @var ServerMapper|\PHPUnit_Framework_MockObject_MockObject  */
	protected $serverMapper;
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Test\TestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();
		$this->serverMapper = $this->createMock(ServerMapper::class);
		$command = new ShowConfig($this->serverMapper);
		$this->commandTester = new CommandTester($command);
		$this->serverMapper
			->method('listAll')
			->willReturn([
				new Server(['id'=>'configId1']),
				new Server(['id'=>'configId2'])
			]);
	}

	/**
	 * @dataProvider providesConfigIds
	 * @param array $input
	 * @param array $expectedToBeContained
	 * @param array $expectedNotToBeContained
	 */
	public function testShowConfigAsTable(
		$input, $expectedToBeContained, $expectedNotToBeContained
	) {
		$this->serverMapper->method('find')
			->willReturn(new Server(['id' => 'random']));
		$this->commandTester->execute($input);
		$output = $this->commandTester->getDisplay();
		foreach ($expectedToBeContained as $expected) {
			\PHPUnit\Framework\Assert::assertContains(
				$expected,
				$output
			);
		}
		foreach ($expectedNotToBeContained as $expected) {
			\PHPUnit\Framework\Assert::assertNotContains(
				$expected,
				$output
			);
		}
	}
	
	/**
	 * @dataProvider providesOutputTypes
	 * @param string $outputType
	 * @return void
	 */
	public function testShowConfigAsJson($outputType) {
		$this->serverMapper->method('find')
			->willReturn(new Server(['id' => 'configId1']));
		$this->commandTester->execute(
			['configID' => 'configId1', '--output' => $outputType]
		);
		$output = $this->commandTester->getDisplay();
		$decodedOutput = \json_decode($output);
		\PHPUnit\Framework\Assert::assertNotNull($decodedOutput);
		\PHPUnit\Framework\Assert::arrayHasKey("ldapBase");
	}

	/**
	 *
	 * @return string[][]
	 */
	public function providesOutputTypes() {
		return [['json'], ['json_pretty']];
	}
	
	/**
	 *
	 * @return string[][][] array with:
	 *				input
	 *				strings expected to be contained in the output
	 *				strings expected not to be contained in the output
	 */
	public function providesConfigIds() {
		return [
			[
				[],
				[
					"+-----------------------+-----------+\n" .
					"| Configuration         | configId1 |\n" .
					"+-----------------------+-----------+\n",
					"+-----------------------+-----------+\n" .
					"| Configuration         | configId2 |\n" .
					"+-----------------------+-----------+\n",
					"| password              | ***       |"
				],
				[]
			],
			[
				['configID' => 'configId1'],
				[
					"+-----------------------+-----------+\n" .
					"| Configuration         | configId1 |\n" .
					"+-----------------------+-----------+\n",
					"| password              | ***       |"
				],
				[
					"+-----------------------+-----------+\n" .
					"| Configuration         | configId2 |\n" .
					"+-----------------------+-----------+\n"
				]
			],
			[
				['--show-password' => true],
				["| password              |           |"],
				["| password              | ***       |"],
			],
		];
	}
}
