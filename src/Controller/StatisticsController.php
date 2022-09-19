<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class StatisticsController extends AbstractController
{
    public function __construct(private ChartBuilderInterface $chartBuilder, private HttpClientInterface $elasticsearchClient)
    {
    }

    #[Route('/stats', name: 'statistics')]
    public function __invoke(Request $request): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirectToRoute('home');
        }

        $response = $this->getStatsFromElastic();

        $normalizedStats = $this->transformFollowerStats($response);

        $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);

        $chart->setOptions([
            'scales' => [
                'xAxes' => [
                    'stacked' => true,
                    'grid' => [
                        'offset' => true,
                    ],
                ],
                'yAxes' => [
                    'stacked' => true,
                ],
            ],
        ]);

        $chart->setData([
            'labels' => $normalizedStats['labels'],
            'datasets' => [
                [
                    'label' => 'youtube',
                    'backgroundColor' => 'rgb(255, 0, 0)',
                    'data' => $normalizedStats['youtube'],
                ],
                [
                    'label' => 'trovo',
                    'backgroundColor' => 'rgb(25, 214, 107)',
                    'data' => $normalizedStats['trovo'],
                ],
                [
                    'label' => 'twitch',
                    'backgroundColor' => 'rgb(140, 68, 247)',
                    'data' => $normalizedStats['twitch'],
                ],
                [
                    'label' => 'brime',
                    'backgroundColor' => 'rgb(177, 56, 130)',
                    'data' => $normalizedStats['brime'],
                ],
            ],
        ]);

        return $this->render('statistics/index.html.twig', [
            'totalFollowers' => $normalizedStats['total_followers'],
            'followerStats' => $chart,
        ]);
    }

    private function transformFollowerStats(array $elasticStats): array
    {
        if (!isset($elasticStats['aggregations']) || count($elasticStats['aggregations']) <= 0) {
            return [];
        }
        $latestData = end($elasticStats['aggregations'][0]['buckets']);

        $stats = ['total_followers' => 0];

        foreach ($elasticStats['aggregations'][0]['buckets'] as $bucket) {
            $stats['labels'][] = (new \DateTime($bucket['key_as_string']))->format('Y M d H:i');

            $isLatest = $latestData === $bucket;

            if (isset($bucket['youtube']['value'])) {
                $stats['youtube'][] = $bucket['youtube']['value'];
                if ($isLatest) {
                    $stats['total_followers'] += $bucket['youtube']['value'];
                }
            } else {
                $stats['youtube'][] = null;
            }

            if (isset($bucket['trovo']['value'])) {
                $stats['trovo'][] = $bucket['trovo']['value'];
                if ($isLatest) {
                    $stats['total_followers'] += $bucket['trovo']['value'];
                }
            } else {
                $stats['trovo'][] = null;
            }

            if (isset($bucket['twitch']['value'])) {
                $stats['twitch'][] = $bucket['twitch']['value'];
                if ($isLatest) {
                    $stats['total_followers'] += $bucket['twitch']['value'];
                }
            } else {
                $stats['twitch'][] = null;
            }

            if (isset($bucket['brime']['value'])) {
                $stats['brime'][] = $bucket['brime']['value'];
                if ($isLatest) {
                    $stats['total_followers'] += $bucket['brime']['value'];
                }
            } else {
                $stats['brime'][] = null;
            }
        }

        return $stats;
    }

    private function getStatsFromElastic(): ?array
    {
        try {
            $response = $this->elasticsearchClient->request('GET', '/*user_stats/_search', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'body' => '
{
  "aggs": {
    "0": {
      "date_histogram": {
        "field": "datetime",
        "fixed_interval": "12h",
        "time_zone": "Europe/Paris",
        "min_doc_count": 1
      },
      "aggs": {
        "brime": {
          "max": {
            "field": "Brime.followerCount"
          }
        },
        "trovo": {
          "max": {
            "field": "Trovo.followerCount"
          }
        },
        "twitch": {
          "max": {
            "field": "Twitch.followerCount"
          }
        },
        "youtube": {
          "max": {
            "field": "Youtube.followerCount"
          }
        }
      }
    }
  },
  "size": 0,
  "stored_fields": [
    "*"
  ],
  "script_fields": {},
  "docvalue_fields": [
    {
      "field": "@timestamp",
      "format": "date_time"
    },
    {
      "field": "datetime",
      "format": "date_time"
    }
  ],
  "_source": {
    "excludes": []
  },
  "query": {
    "bool": {
      "must": [],
      "filter": [
        {
          "bool": {
            "should": [
              {
                "match": {
                  "userId": '.$this->getUser()->getId().'
                }
              }
            ],
            "minimum_should_match": 1
          }
        },
        {
          "match_all": {}
        },
        {
          "range": {
            "datetime": {
              "format": "strict_date_optional_time",
              "gte": "'.(new \DateTime())->sub(new \DateInterval('P1M'))->format('Y-m-d\TH:i:s.u\Z').'",
              "lte": "'.(new \DateTime('now'))->format('Y-m-d\TH:i:s.u\Z').'"
            }
          }
        }
      ],
      "should": [],
      "must_not": []
    }
  }
}',
            ]);

            return $response->toArray();
        } catch (ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            return null;
        }
    }
}
