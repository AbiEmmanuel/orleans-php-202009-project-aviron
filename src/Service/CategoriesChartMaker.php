<?php

namespace App\Service;

use App\Repository\SubscriptionRepository;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class CategoriesChartMaker extends ChartMaker
{
    private const CATEGORIES_PALETTE = [
        '#95C0CE',
        '#5D95AA',
        '#004C6D',
    ];

    private const CATEGORIES_GROUPS = [
        'Jeunes' => ['J9', 'J10', 'J11', 'J12', 'J13', 'J14'],
        'Juniors' => ['J15', 'J16', 'J17', 'J18'],
        'Seniors' => ['S-23', 'S']
    ];

    private const CATEGORIES_LABELS = ['Jeunes', 'Juniors', 'Seniors'];

    /**
     * @var SubscriptionRepository
     */
    private SubscriptionRepository $subscriptionRepository;

    public function __construct(
        SubscriptionRepository $subscriptionRepository,
        ChartBuilderInterface $chartBuilder
    ) {
        parent::__construct($chartBuilder);
        $this->subscriptionRepository = $subscriptionRepository;
    }

    public function getChart(?string $season): Chart
    {

        $subscriptionsByCategories = $this->subscriptionRepository->subscribersByYearByCategories($season);
        $subscriptionsData = [0, 0, 0];

        foreach ($subscriptionsByCategories as $subscriptionsByCategory) {
            if (in_array($subscriptionsByCategory['label'], self::CATEGORIES_GROUPS['Jeunes'])) {
                $subscriptionsData[0] += $subscriptionsByCategory['subscribersCount'];
            } elseif (in_array($subscriptionsByCategory['label'], self::CATEGORIES_GROUPS['Juniors'])) {
                $subscriptionsData[1] += $subscriptionsByCategory['subscribersCount'];
            } elseif (in_array($subscriptionsByCategory['label'], self::CATEGORIES_GROUPS['Seniors'])) {
                $subscriptionsData[2] += $subscriptionsByCategory['subscribersCount'];
            }
        }

        $categoriesChart = $this->chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $categoriesChart->setData([
            'labels' => self::CATEGORIES_LABELS,
            'datasets' => [
                [
                    'backgroundColor' => self::CATEGORIES_PALETTE,
                    'data' => $subscriptionsData,
                ],
            ]
        ]);

        $categoriesChart->setOptions([
            'legend' => ([
                'position' => 'bottom',
            ]),

        ]);

        return $categoriesChart;
    }
}
