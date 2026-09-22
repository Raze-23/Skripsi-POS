<?php

namespace App\Services;

use App\Models\SawPrioritySnapshot;
use Illuminate\Support\Facades\DB;

class SawPriorityMovementTracker
{
    public function track(array $rankedProducts, int $year): array
    {
        if ($rankedProducts === []) {
            return [];
        }

        return DB::transaction(function () use ($rankedProducts, $year): array {
            $snapshots = SawPrioritySnapshot::query()
                ->where('year', $year)
                ->whereIn('product_id', array_column($rankedProducts, 'id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            foreach ($rankedProducts as $index => &$product) {
                $rank = $index + 1;
                $score = round((float) $product['score'], 4);
                $metrics = $this->metricsFrom($product);
                $snapshot = $snapshots->get($product['id']);

                if (! $snapshot) {
                    $snapshot = SawPrioritySnapshot::create([
                        'product_id' => $product['id'],
                        'year' => $year,
                        'current_rank' => $rank,
                        'current_score' => $score,
                        'current_metrics' => $metrics,
                        'changed_at' => now(),
                    ]);

                    $snapshots->put($product['id'], $snapshot);
                } elseif ($this->hasChanged($snapshot, $rank, $score, $metrics)) {
                    $snapshot->update([
                        'previous_rank' => $snapshot->current_rank,
                        'previous_score' => $snapshot->current_score,
                        'previous_metrics' => $snapshot->current_metrics,
                        'current_rank' => $rank,
                        'current_score' => $score,
                        'current_metrics' => $metrics,
                        'changed_at' => now(),
                    ]);
                }

                $product['poin_saw'] = round($score * 100, 2);
                $product['movement'] = $this->movementFrom($snapshot);
            }

            unset($product);

            return $rankedProducts;
        });
    }

    private function metricsFrom(array $product): array
    {
        return [
            'penjualan' => (int) $product['c1_display'],
            'kedaluwarsa' => $product['c2_display'] === null ? null : (int) $product['c2_display'],
            'stok' => (int) $product['c3_display'],
            'req_mitra' => (int) $product['c4_display'],
            'req_owner' => (int) $product['c5_display'],
        ];
    }

    private function hasChanged(
        SawPrioritySnapshot $snapshot,
        int $rank,
        float $score,
        array $metrics,
    ): bool {
        return $snapshot->current_rank !== $rank
            || abs($snapshot->current_score - $score) >= 0.0001
            || $this->metricsDiffer($snapshot->current_metrics, $metrics);
    }

    private function movementFrom(SawPrioritySnapshot $snapshot): array
    {
        $previousRank = $snapshot->previous_rank;
        $previousScore = $snapshot->previous_score;
        $rankDelta = $previousRank === null ? null : $previousRank - $snapshot->current_rank;
        $scoreDelta = $previousScore === null
            ? null
            : round($snapshot->current_score - $previousScore, 4);

        $poinDelta = $scoreDelta === null ? null : round($scoreDelta * 100, 2);

        [$type, $label] = match (true) {
            $previousRank === null => ['new', 'Baru dianalisis'],
            $rankDelta > 0 => ['up', $this->formatRankLabel('Naik', $rankDelta, $scoreDelta)],
            $rankDelta < 0 => ['down', $this->formatRankLabel('Turun', abs($rankDelta), $scoreDelta)],
            $scoreDelta > 0 => ['score_up', 'Skor +'.number_format($scoreDelta, 4, ',', '.')],
            $scoreDelta < 0 => ['score_down', 'Skor '.number_format($scoreDelta, 4, ',', '.')],
            $this->metricsDiffer($snapshot->previous_metrics, $snapshot->current_metrics) => ['changed', 'Data kriteria berubah'],
            default => ['stable', 'Belum ada perubahan'],
        };

        return [
            'type' => $type,
            'label' => $label,
            'rank_delta' => $rankDelta,
            'score_delta' => $scoreDelta,
            'poin_delta' => $poinDelta,
            'criteria_deltas' => $this->criteriaDeltas($snapshot),
            'changed_at' => $snapshot->changed_at,
        ];
    }

    private function formatRankLabel(string $arah, int $rankDelta, ?float $scoreDelta): string
    {
        $label = $arah.' '.$rankDelta.' Peringkat';

        if ($scoreDelta !== null && $scoreDelta != 0.0) {
            $tanda = $scoreDelta > 0 ? '+' : '-';
            // Ubah menjadi 4 angka desimal, dan ganti kata 'Poin' menjadi 'Skor'
            $label .= ' ('.$tanda.number_format(abs($scoreDelta), 4, ',', '.').' Skor)';
        }

        return $label;
    }

    private function criteriaDeltas(SawPrioritySnapshot $snapshot): array
    {
        if ($snapshot->previous_metrics === null) {
            return [];
        }

        $deltas = [];

        foreach ($snapshot->current_metrics as $criterion => $currentValue) {
            $previousValue = $snapshot->previous_metrics[$criterion] ?? null;

            if ($currentValue === null || $previousValue === null) {
                $deltas[$criterion] = null;

                continue;
            }

            $deltas[$criterion] = (int) $currentValue - (int) $previousValue;
        }

        return $deltas;
    }

    private function metricsDiffer(?array $first, ?array $second): bool
    {
        if ($first === null || $second === null) {
            return $first !== $second;
        }

        return $first != $second;
    }
}
