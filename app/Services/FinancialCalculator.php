<?php

namespace App\Services;

class FinancialCalculator
{
    public function calculateEmi(float $principal, float $annualRate, int $tenureYears): array
    {
        $monthlyRate = ($annualRate / 100) / 12;
        $tenureMonths = $tenureYears * 12;

        if ($monthlyRate == 0) {
            $emi = $principal / $tenureMonths;
        } else {
            $emi = ($principal * $monthlyRate * pow(1 + $monthlyRate, $tenureMonths)) / 
                   (pow(1 + $monthlyRate, $tenureMonths) - 1);
        }

        $totalPayment = $emi * $tenureMonths;
        $totalInterest = $totalPayment - $principal;

        return [
            'emi' => round($emi, 2),
            'total_payment' => round($totalPayment, 2),
            'total_interest' => round($totalInterest, 2),
        ];
    }

    public function calculateFutureValue(float $presentValue, float $annualRate, int $tenureYears, int $compoundingFrequency): float
    {
        $ratePerPeriod = ($annualRate / 100) / $compoundingFrequency;
        $totalPeriods = $tenureYears * $compoundingFrequency;

        $futureValue = $presentValue * pow(1 + $ratePerPeriod, $totalPeriods);

        return round($futureValue, 2);
    }
}
