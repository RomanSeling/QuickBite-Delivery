<?php

class StatistikyController
{
    public static function getData(): array
    {
        $model        = new Objednavka();
        $statusCounts = $model->getStatusCounts();

        return [
            'statusCounts'     => $statusCounts,
            'stats'            => $model->getDashboardStats(),
            'statsByMonth'     => $model->getStatsByMonth(),
            'topRestauracie'   => $model->getTopRestauracie(5),
            'novychObjednavok' => $statusCounts['nova'] ?? 0,
        ];
    }
}
