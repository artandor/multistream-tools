<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class StatisticsController extends AbstractController
{
    public function __construct(private ChartBuilderInterface $chartBuilder, private HttpClientInterface $client)
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
                ],
                'yAxes' => [
                    'stacked' => true,
                ]
            ],
        ]);

        $chart->setData([
            'labels' => $normalizedStats['labels'],
            'datasets' => [
                [
                    'label' => 'youtube',
                    'backgroundColor' => 'rgb(255, 0, 0)',
                    'data' => $normalizedStats["youtube"],
                ],
                [
                    'label' => 'trovo',
                    'backgroundColor' => 'rgb(25, 214, 107)',
                    'data' => $normalizedStats["trovo"],
                ],
                [
                    'label' => 'twitch',
                    'backgroundColor' => 'rgb(140, 68, 247)',
                    'data' => $normalizedStats["twitch"],
                ],
                [
                    'label' => 'brime',
                    'backgroundColor' => 'rgb(177, 56, 130)',
                    'data' => $normalizedStats["brime"],
                ],
            ]
        ]);

        return $this->render('statistics/index.html.twig', [
            'followerStats' => $chart,
        ]);
    }

    private function transformFollowerStats(array $elasticStats): array
    {
        $stats = [];
        foreach ($elasticStats["aggregations"][0]["buckets"] as $bucket) {
            $stats["labels"][] = (new \DateTime($bucket["key_as_string"]))->format('Y M d H:i');

            if (isset($bucket["youtube"]["hits"]["hits"][0]["fields"])) {
                $stats["youtube"][] = $bucket["youtube"]["hits"]["hits"][0]["fields"]["data.Youtube.followerCount"][0];
            } else {
                $stats["youtube"][] = null;
            }

            if (isset($bucket["trovo"]["hits"]["hits"][0]["fields"])) {
                $stats["trovo"][] = $bucket["trovo"]["hits"]["hits"][0]["fields"]["data.Trovo.followerCount"][0];
            } else {
                $stats["trovo"][] = null;
            }

            if (isset($bucket["twitch"]["hits"]["hits"][0]["fields"])) {
                $stats["twitch"][] = $bucket["twitch"]["hits"]["hits"][0]["fields"]["data.Twitch.followerCount"][0];
            } else {
                $stats["twitch"][] = null;
            }

            if (isset($bucket["brime"]["hits"]["hits"][0]["fields"])) {
                $stats["brime"][] = $bucket["brime"]["hits"]["hits"][0]["fields"]["data.Brime.followerCount"][0];
            } else {
                $stats["brime"][] = null;
            }
        }
        return $stats;
    }

    private function getStatsFromElastic(): array
    {
        $response = $this->client->request('GET', 'http://elasticsearch:9200/*user_stats/_search', [
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
        "fixed_interval": "1h",
        "time_zone": "Europe/Paris"
      },
      "aggs": {
        "brime": {
          "top_hits": {
            "fields": [
              {
                "field": "data.Brime.followerCount"
              }
            ],
            "_source": false,
            "size": 1,
            "sort": [
              {
                "datetime": {
                  "order": "desc"
                }
              }
            ]
          }
        },
        "trovo": {
          "top_hits": {
            "fields": [
              {
                "field": "data.Trovo.followerCount"
              }
            ],
            "_source": false,
            "size": 1,
            "sort": [
              {
                "datetime": {
                  "order": "desc"
                }
              }
            ]
          }
        },
        "twitch": {
          "top_hits": {
            "fields": [
              {
                "field": "data.Twitch.followerCount"
              }
            ],
            "_source": false,
            "size": 1,
            "sort": [
              {
                "datetime": {
                  "order": "desc"
                }
              }
            ]
          }
        },
        "youtube": {
          "top_hits": {
            "fields": [
              {
                "field": "data.Youtube.followerCount"
              }
            ],
            "_source": false,
            "size": 1,
            "sort": [
              {
                "datetime": {
                  "order": "desc"
                }
              }
            ]
          }
        }
      }
    }
  },
  "size": 0,
  "fields": [
    {
      "field": "@timestamp",
      "format": "date_time"
    },
    {
      "field": "datetime",
      "format": "date_time"
    }
  ],
  "script_fields": {},
  "stored_fields": [
    "*"
  ],
  "runtime_mappings": {},
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
                  "data.userId": "' . $this->getUser()->getId() . '"
                }
              }
            ],
            "minimum_should_match": 1
          }
        },
        {
          "range": {
            "datetime": {
              "format": "strict_date_optional_time",
              "gte": "' . (new \DateTime())->sub(new \DateInterval("P1M"))->format('Y-m-d\TH:i:s.u\Z') . '",
              "lte": "' . (new \DateTime('now'))->format('Y-m-d\TH:i:s.u\Z') .'"
            }
          }
        }
      ],
      "should": [],
      "must_not": []
    }
  }
}'
        ]);

        return $response->toArray();
    }
}
