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
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;

/**
 * Class CommandTest
 *
 */
class CommandTest extends TestCase {
	/** @var CommandTester */
	private $commandTester;
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Test\TestCase::setUp()
	 */
	protected function setUp() {
		parent::setUp();
		$this->helper = $this->createMock(Helper::class);
		$coreConfig = \OC::$server->getConfig();
		$command = new ShowConfig($coreConfig, $this->helper);
		$this->commandTester = new CommandTester($command);
		$this->helper
		->expects($this->once())
		->method("getServerConfigurationPrefixes")
		->willReturn(["configId1","configId2"]);
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
		$this->commandTester->execute($input);
		$output = $this->commandTester->getDisplay();
		foreach ($expectedToBeContained as $expected) {
			\PHPUnit_Framework_Assert::assertContains(
				$expected,
				$output
			);
		}
		foreach ($expectedNotToBeContained as $expected) {
			\PHPUnit_Framework_Assert::assertNotContains(
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
		$this->commandTester->execute(
			['configID' => 'configId1', '--output' => $outputType]
		);
		$output = $this->commandTester->getDisplay();
		$decodedOutput = \json_decode($output);
		\PHPUnit_Framework_Assert::assertNotNull($decodedOutput);
		\PHPUnit_Framework_Assert::arrayHasKey("ldapBase");
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
					"+-------------------------------+----------------+\n" .
					"| Configuration                 | configId1      |\n" .
					"+-------------------------------+----------------+\n",
					"+-------------------------------+----------------+\n" .
					"| Configuration                 | configId2      |\n" .
					"+-------------------------------+----------------+\n",
					"| ldapAgentPassword             | ***            |"
				],
				[]
			],
			[
				['configID' => 'configId1'],
				[
					"+-------------------------------+----------------+\n" .
					"| Configuration                 | configId1      |\n" .
					"+-------------------------------+----------------+\n",
					"| ldapAgentPassword             | ***            |"
				],
				[
					"+-------------------------------+----------------+\n" .
					"| Configuration                 | configId2      |\n" .
					"+-------------------------------+----------------+\n"
				]
			],
			[
				['--show-password' => true],
				["| ldapAgentPassword             |                |"],
				["| ldapAgentPassword             | ***            |"],
			],
		];
	}
}
