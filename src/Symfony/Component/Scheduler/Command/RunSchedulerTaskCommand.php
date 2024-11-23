<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Scheduler\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Scheduler\Messenger\ServiceCallMessage;
use Symfony\Component\Scheduler\Messenger\ServiceCallMessageHandler;

/**
 * Command to run scheduler tasks.
 *
 * @author Jannes Drijkoningen <jannesdrijkoningen@gmail.com>
 */
#[AsCommand(name: 'scheduler:run', description: 'Run a scheduled task')]
final class RunSchedulerTaskCommand extends Command
{
    public function __construct(private ServiceCallMessageHandler $serviceCallMessageHandler)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('handler', InputArgument::REQUIRED, 'The FQCN of the task handler to run');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Run scheduler task handler.');

        $handler = $input->getArgument('handler');

        $handlersLocator = new HandlersLocator([
            ServiceCallMessage::class => [$this->serviceCallMessageHandler],
        ]);

        $messageBus = new MessageBus([
            new HandleMessageMiddleware($handlersLocator),
        ]);

        $io = new SymfonyStyle($input, $output);
        $io->info('Running schedule '.$handler);

        $message = new ServiceCallMessage($handler);

        try {
            $messageBus->dispatch($message);
            $io->success('Task ran successfully.');

            return self::SUCCESS;
        } catch (ExceptionInterface $ex) {
            $io->error(\sprintf('Unable to run %s. Are you sure it exists?', $handler));

            return self::FAILURE;
        }
    }
}
