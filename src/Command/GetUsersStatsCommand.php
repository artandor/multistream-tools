<?php

namespace App\Command;

use App\Provider\AbstractPlatformProvider;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:get-users-stats',
    description: 'Get users stats for all platforms and log them for logstash to collect',
)]
class GetUsersStatsCommand extends Command
{
    public function __construct(private UserRepository $userRepository,
                                private EntityManagerInterface $em,
                                private LoggerInterface $logstashLogger)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $users = $this->userRepository->findAll();

        foreach ($users as $user) {
            $resultData = ['total' => 0, 'userId' => $user->getId()];
            foreach ($user->getAccounts() as $account) {
                $platform = $account->getPlatform();

                /** @var AbstractPlatformProvider $provider */
                $provider = new ($platform->getProvider())($this->em, $this->logstashLogger);

                $followerCount = $provider->getFollowerCount($account);
                $io->info('Follower Count for User ' . $user->getEmail() . ' : ' . $followerCount);

                $resultData[$platform->getName()] = [
                    'followerCount' => $followerCount,
                ];
                $resultData['total'] = $resultData['total'] + $followerCount;
            }
            $this->logstashLogger->log(LogLevel::INFO, json_encode($resultData));
        }

        return Command::SUCCESS;
    }
}
