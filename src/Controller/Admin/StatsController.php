<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use DateInterval;
use DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/stats')]
class StatsController extends AbstractController
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    #[Route('/users', name: 'admin_user_stats')]
    public function userStats(ChartBuilderInterface $chartBuilder): Response
    {
        $thirtyDaysAgo = (new DateTime())->sub(DateInterval::createFromDateString('30 days'));
        $usersByDay = $this->userRepository->findNewUsersSinceDate($thirtyDaysAgo);

        $cleanedData = [];
        foreach ($usersByDay as $day) {
            $cleanedData[$day['dateCreated']] = $day[1];
        }

        $labels = [];
        $data = [];
        for ($i = 30; $i >= 0; --$i) {
            $date = date('Y-m-d', strtotime('-'.$i.' days'));
            $labels[] = $date;
            if (array_key_exists($date, $cleanedData)) {
                $data[] = $cleanedData[$date];
            } else {
                $data[] = 0;
            }
        }

        $chart = $chartBuilder->createChart(Chart::TYPE_BAR);

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Amount of new users per day in the last 30 days',
                    'backgroundColor' => 'rgb(255, 99, 132)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'data' => $data,
                ],
            ],
        ]);

        $chart->setOptions([
            'scales' => [
                'y' => [
                    'suggestedMin' => 0,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ]);

        return $this->render('admin/stats.html.twig', [
            'chart' => $chart,
            'total_users' => $this->userRepository->count([]),
            'stat_title' => 'Users',
        ]);
    }
}
