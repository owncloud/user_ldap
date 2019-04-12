<?php

namespace OCA\User_LDAP\Command;

use OCA\User_LDAP\Config\ConfigMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;

class DeleteConfigTest extends TestCase {
	/** @var ConfigMapper | MockObject */
	private $mapper;

	/** @var CommandTester */
	private $commandTester;

	protected function setUp() {
		parent::setUp();
		$this->mapper = $this->createMock(ConfigMapper::class);
		$command = new DeleteConfig($this->mapper);
		$this->commandTester = new CommandTester($command);
	}

	public function testDeleteExisting() {
		$configId = 'testConfigId';
		$this->commandTester->execute(['configID' => $configId]);
		$output = $this->commandTester->getDisplay();
		$this->assertContains("Deleted configuration with configID '{$configId}'", $output);
	}

	public function testDeleteNotExisting() {
		$configId = 'testConfigId';
		$this->mapper->method('delete')->willThrowException(new DoesNotExistException(''));
		$this->commandTester->execute(['configID' => $configId]);
		$output = $this->commandTester->getDisplay();
		$this->assertContains("Configuration with configID '$configId' does not exist", $output);
	}
}
