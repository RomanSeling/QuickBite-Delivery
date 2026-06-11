<?php

class DashboardController
{
    public static function getData(): array
    {
        $model        = new Objednavka();
        $stats        = $model->getDashboardStats();
        $statusCounts = $model->getStatusCounts();

        return [
            'stats'             => $stats,
            'statusCounts'      => $statusCounts,
            'posledneObjednavky'=> array_slice($model->all(), 0, 10),
            'topRestauracie'    => $model->getTopRestauracie(4),
            'novychObjednavok'  => $statusCounts['nova'] ?? 0,
        ];
    }
}
