<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Scheduler\Tests\Command;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Scheduler\Command\RunSchedulerTaskCommand;
use Symfony\Component\Scheduler\Messenger\ServiceCallMessageHandler;

/**
 * Tests for scheduler task runner command
 * @see RunSchedulerTaskCommand
 *
 * @author Jannes Drijkoningen <jannesdrijkoningen@gmail.com>
 */
class RunSchedulerTaskCommandTest extends TestCase
{
    public function testWithExistingHandler()
    {
        $handler = new class {
            public bool $executed = false;
            public function __invoke(): void
            {
                $this->executed = true;
            }
        };

        $serviceLocator = $this->createMock(ContainerInterface::class);
        $serviceLocator
            ->expects($this->once())
            ->method('get')
            ->with('TestHandler')
            ->willReturn($handler);

        $serviceCallMessageHandler = new ServiceCallMessageHandler($serviceLocator);
        $command = new RunSchedulerTaskCommand($serviceCallMessageHandler);
        $tester = new CommandTester($command);

        $tester->execute(['handler' => 'TestHandler']);
        $this->assertTrue($handler->executed);
        $this->assertStringContainsString('Task ran successfully', $tester->getDisplay(true));
    }

    public function testWithNonExistingHandler()
    {
        $serviceCallMessageHandler = $this->createMock(ServiceCallMessageHandler::class);
        $serviceCallMessageHandler
            ->expects($this->once())
            ->method('__invoke')
            ->willThrowException($this->createMock(HandlerFailedException::class));

        $command = new RunSchedulerTaskCommand($serviceCallMessageHandler);
        $tester = new CommandTester($command);

        $tester->execute(['handler' => 'not-a-real-handler']);
        $this->assertStringContainsString('Unable to run not-a-real-handler', $tester->getDisplay(true));
    }
}
