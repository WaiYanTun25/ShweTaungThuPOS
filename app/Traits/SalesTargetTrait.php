<?php

namespace App\Traits;

use App\Models\Sale;
use Carbon\Carbon;
use stdClass;

trait SalesTargetTrait
{
    private function getSignOfPercentageChange($percentageChange)
    {
        return ($percentageChange > 0) ? '+' . $percentageChange . "%" : $percentageChange . "%";
    }

    private function calculatePercentageChange($todayValue, $yesterdayValue)
    {
        return round(($todayValue - $yesterdayValue) / ($yesterdayValue != 0 ? $yesterdayValue : 1) * 100);
    }

    private function calculatePercentageChangeTarget($todayValue, $yesterdayValue)
    {
        return ($yesterdayValue != 0) ? (($todayValue - $yesterdayValue) / abs($yesterdayValue) * 100) : 0;
    }

    public function getSalesTargetByProduct($period, $amount)
    {
        if ($period == "week") {
            // Code for the week period
            $startDate = Carbon::now()->startOfWeek(); // Start of the current week
            $endDate = Carbon::now()->endOfWeek(); // Today, end of the day
        
            $prevStartDate = Carbon::now()->startOfWeek()->subWeek(); // Start of the previous week
            $prevEndDate = Carbon::now()->endOfWeek()->subWeek(); // End of the previous week
        
        } elseif ($period == "month") {
            // Code for the month period
            $startDate = Carbon::now()->startOfMonth(); // Start of the current month
            $endDate = Carbon::now()->endOfMonth(); // Today, end of the day
        
            $prevStartDate = Carbon::now()->startOfMonth()->subMonth(); // Start of the previous month
            $prevEndDate = Carbon::now()->endOfMonth()->subMonth(); // End of the previous month
        
        } elseif ($period == "year") {
            // Code for the year period
            $startDate = Carbon::now()->startOfYear(); // Start of the current year
            $endDate = Carbon::now()->endOfYear(); // Today, end of the day
        
            $prevStartDate = Carbon::now()->startOfYear()->subYear(); // Start of the previous year
            $prevEndDate = Carbon::now()->endOfYear()->subYear(); // End of the previous year
        }
        
        // Retrieve sales data for the current and previous period
        $getThisPeriodSalesData = Sale::whereBetween('sales_date', [$startDate, $endDate])->sum('total_quantity');
        $getPrevPeriodSalesData = Sale::whereBetween('sales_date', [$prevStartDate, $prevEndDate])->sum('total_quantity');
        // Calculate the percentage change
        $productPercentageChange = $this->calculatePercentageChange($getThisPeriodSalesData, $getPrevPeriodSalesData);
        $percentageThenPerv = $this->getSignOfPercentageChange($productPercentageChange);

        // Calculate the percentage change for target
        $productPercentageChangeTarget = ($getThisPeriodSalesData / $amount) * 100 ;
        
        $results = new stdClass;
        $results->total_amount = $getThisPeriodSalesData;
        $results->percentage = $percentageThenPerv;
        $results->target_percentage = $productPercentageChangeTarget > 100 ? 100 . "%" : $productPercentageChangeTarget . "%";

        return $results;
    }

    public function getSalesTargetBySalesAmount($period, $amount)
    {
        if ($period == "week") {
            // Code for the week period
            $startDate = Carbon::now()->startOfWeek(); // Start of the current week
            $endDate = Carbon::now()->endOfWeek(); // Today, end of the day
        
            $prevStartDate = Carbon::now()->startOfWeek()->subWeek(); // Start of the previous week
            $prevEndDate = Carbon::now()->endOfWeek()->subWeek(); // End of the previous week
        
        } elseif ($period == "month") {
            // Code for the month period
            $startDate = Carbon::now()->startOfMonth(); // Start of the current month
            $endDate = Carbon::now()->endOfMonth(); // Today, end of the day
        
            $prevStartDate = Carbon::now()->startOfMonth()->subMonth(); // Start of the previous month
            $prevEndDate = Carbon::now()->endOfMonth()->subMonth(); // End of the previous month
        
        } elseif ($period == "year") {
            // Code for the year period
            $startDate = Carbon::now()->startOfYear(); // Start of the current year
            $endDate = Carbon::now()->endOfYear(); // Today, end of the day
        
            $prevStartDate = Carbon::now()->startOfYear()->subYear(); // Start of the previous year
            $prevEndDate = Carbon::now()->endOfYear()->subYear(); // End of the previous year
        }
        
        /****** Retrieve sales data for the current and previous period *****/
        $getThisPeriodSalesData = Sale::whereBetween('sales_date', [$startDate, $endDate])->sum('total_amount');
        $getPrevPeriodSalesData = Sale::whereBetween('sales_date', [$prevStartDate, $prevEndDate])->sum('total_amount');
        /****** Calculate the percentage change ******/
        $productPercentageChange = $this->calculatePercentageChange($getThisPeriodSalesData, $getPrevPeriodSalesData);
        $percentageThenPerv = $this->getSignOfPercentageChange($productPercentageChange);
        /****** Calculate the percentage change for target ******/
        $productPercentageChangeTarget = ( $getThisPeriodSalesData / $amount ) * 100 ;
        
        $results = new stdClass;
        $results->total_amount = $getThisPeriodSalesData;
        $results->percentage = $percentageThenPerv;
        // $results->target_percentage = $productPercentageChangeTarget . "%";
        $results->target_percentage = $productPercentageChangeTarget > 100 ? 100 . "%" : $productPercentageChangeTarget . "%";

        return $results;
    }
}
