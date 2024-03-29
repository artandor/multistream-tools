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
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:get-users-stats',
    description: 'Get users stats for all platforms and log them for logstash to collect',
)]
class GetUsersStatsCommand extends Command
{
    public function __construct(private UserRepository $userRepository,
                                private EntityManagerInterface $em,
                                private LoggerInterface $logger,
                                private HttpClientInterface $elasticsearchClient
    ) {
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
                $provider = new ($platform->getProvider())($this->em, $this->logger);

                $followerCount = $provider->getFollowerCount($account);

                // If the platform returned no data or 0, do not send stats.
                if (!$followerCount || $followerCount <= 0) {
                    continue;
                }
                $resultData[$platform->getName()] = [
                    'followerCount' => $followerCount,
                ];
                $resultData['total'] = $resultData['total'] + $followerCount;
                $resultData['datetime'] = (new \DateTime())->format('Y-m-d H:m:s');
            }

            try {
                $response = $this->elasticsearchClient->request(
                    'POST',
                    '/logstash-user_stats/_doc',
                    [
                        'json' => $resultData,
                    ],
                );
            } catch (TransportExceptionInterface $e) {
                $this->logger->log(LogLevel::CRITICAL, 'Error while posting data to elastic search : '.$e->getMessage());
            }
            // @todo : Would it be great to send data as batch ? Reduces the charge over ES, but increase the risk of global failure.

            $io->info('Follower Count for User '.$user->getEmail().' : '.$resultData['total']);
        }

        return Command::SUCCESS;
    }
}
