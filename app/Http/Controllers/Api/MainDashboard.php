<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\NewSalesTargetRequest;
use App\Http\Resources\SalesTargetResource;
use App\Http\Resources\TopPerformingResource;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\ItemUnitDetail;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\SalesOrder;
use App\Models\SalesTarget;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Traits\SalesTargetTrait;
use stdClass;

class MainDashboard extends ApiBaseController
{
    use SalesTargetTrait;
    private function getSignOfPercentageChange($percentageChange)
    {
        return ($percentageChange > 0) ? '+' . $percentageChange . "%" : $percentageChange . "%";
    }

    private function calculatePercentageChange($todayValue, $yesterdayValue)
    {
        return round(($todayValue - $yesterdayValue) / ($yesterdayValue != 0 ? $yesterdayValue : 1) * 100);
    }

    private function getCustomers()
    {
        $todayCustomers = Customer::whereDate('join_date', Carbon::today())->get();
        $yesterdayCustomers = Customer::whereDate('join_date', Carbon::yesterday())->get();

        $todayCustomersCount = $todayCustomers->count();
        $yesterdayCustomersCount = $yesterdayCustomers->count();

        $customerPercentageChange = $this->calculatePercentageChange($todayCustomersCount, $yesterdayCustomersCount);

        $result = new stdClass;
        $result->amount = $todayCustomersCount;
        $result->percentage = $this->getSignOfPercentageChange($customerPercentageChange);
        return $result;
    }

    private function getSalesData($todaySales, $yesterdaySales, $field)
    {
        $todaySum = $todaySales->sum($field);
        $yesterdaySum = $yesterdaySales->sum($field);

        $percentageChange = $this->calculatePercentageChange($todaySum, $yesterdaySum);

        $result = new stdClass;
        $result->amount = $field == 'total_amount' ? $todaySum . " Kyat" : $todaySum;
        $result->percentage = $this->getSignOfPercentageChange($percentageChange);

        return $result;
    }

    public function todaySalesRate()
    {
        $todaySales = Sale::whereDate('sales_date', Carbon::today())->get();
        $yesterdaySales = Sale::whereDate('sales_date', Carbon::yesterday())->get();

        $resultAmount = $this->getSalesData($todaySales, $yesterdaySales, 'total_amount');
        $resultQuantity = $this->getSalesData($todaySales, $yesterdaySales, 'total_quantity');

        $result = new stdClass;
        $result->today_sales_rates = [
            'total_sales_rate' => $resultAmount,
            'total_order_rate' => $this->totalSalesOrder(),
            'total_sales_quantity' => $resultQuantity,
            'total_today_customers' => $this->getCustomers()
        ];

        
        return $this->sendSuccessResponse('success', Response::HTTP_OK, $result);
    }

    public function totalSalesOrder()
    {
        $todaySalesOrder = SalesOrder::whereDate('order_date', Carbon::today())->get();
        $yesterdaySalesOrder = SalesOrder::whereDate('order_date', Carbon::yesterday())->get();

        $todayCount = count($todaySalesOrder);
        $yesterdayCount = count($yesterdaySalesOrder);

        $percentageChange = $this->calculatePercentageChange($todayCount, $yesterdayCount);

        $result = new stdClass;
        $result->amount = $todayCount;
        $result->percentage = $this->getSignOfPercentageChange($percentageChange);

        return $result;
    }

    public function stockSummary()
    {
        $getTotalStocks = Inventory::when(Auth::user()->branch_id !== 0, function ($query) {
            $query->where('inventories.branch_id', Auth::user()->branch_id);
        })
        ->sum('quantity');

        $getItemUnitCount = ItemUnitDetail::count();

        $countLowStocks = ItemUnitDetail::with('item')
            ->join('inventories', function ($join) {
                $join->on('item_unit_details.item_id', '=', 'inventories.item_id')
                    ->on('item_unit_details.unit_id', '=', 'inventories.unit_id')
                    ->when(Auth::user()->branch_id !== 0, function ($query) {
                        $query->where('inventories.branch_id', Auth::user()->branch_id);
                    })
                    ->where('inventories.quantity', '<', DB::raw('item_unit_details.reorder_level'));
            })->count();
        
        $countOutOfStocks = Inventory::when(Auth::user()->branch_id !== 0, function ($query) {
                $query->where('inventories.branch_id', Auth::user()->branch_id);
            })
            ->where('quantity', '<=', 0)
            ->count();

        $result = new stdClass;
        $result->total_stock = $getTotalStocks;
        $result->total_items = $getItemUnitCount;
        $result->total_lowstocks = $countLowStocks;
        $result->total_outstocks = $countOutOfStocks;
            
        return $this->sendSuccessResponse('success', Response::HTTP_OK, $result);
    }

    public function salesRate(Request $request)
    {
        $durationBy = $request->query('durationBy');

        switch($durationBy){
            case "week":
                return $this->salesRateByWeek();
                break;
            case "month":
                return $this->salesRateByMonth();
                break;
            case "year":
                return $this->salesRateByYear();
                break;
            default:
                return $this->sendErrorResponse("Please choose correct duration", Response::HTTP_BAD_REQUEST);
        }
    }

    private function salesRateByWeek()
    {
        $weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        // Create a collection of all days of the week
        $allDays = new Collection($weekDays);

        // Get the start and end of the week
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        // Query sales data and left join with the collection of all days
        $thisWeekSales = $allDays->map(function ($day) use ($startOfWeek, $endOfWeek) {
            $result = Sale::selectRaw("'$day' as day_name, COALESCE(SUM(total_amount), 0) as total_amount")
                ->whereBetween('sales_date', [$startOfWeek, $endOfWeek])
                ->whereRaw("DATE_FORMAT(sales_date, '%a') = ?", [$day]) // Use DATE_FORMAT instead of DAYNAME
                ->groupBy('day_name')
                ->first();

            return $result ?: (object)['day_name' => $day, 'total_amount' => '0'];
        });

        return $this->sendSuccessResponse('success', Response::HTTP_OK, ['duration_by_week' => $thisWeekSales]);
    }

    private function salesRateByMonth()
    {
        // Get the first and last day of the current month
        $firstDayOfMonth = Carbon::now()->startOfMonth();
        $lastDayOfMonth = Carbon::now()->endOfMonth();

        // Generate an array of all days of the month (as numbers)
        $allDaysOfMonth = range(1, $lastDayOfMonth->day);

        // Query sales data and left join with the collection of all days
        $thisMonthSales = collect($allDaysOfMonth)->map(function ($day) use ($firstDayOfMonth) {
            $currentDate = $firstDayOfMonth->copy()->addDays($day - 1);

            $result = Sale::select(DB::raw("DAY(sales_date) as day_number, COALESCE(SUM(total_amount), 0) as total_amount"))
                ->where(DB::raw("DATE_FORMAT(sales_date, '%Y-%m-%d')"), $currentDate->toDateString())
                ->groupBy('day_number')
                ->first();

            return $result ?: (object)['day_number' => $day, 'total_amount' => '0'];
        });

        return $this->sendSuccessResponse('success', Response::HTTP_OK, ["duration_by_month" => $thisMonthSales]);
    }

    private function salesRateByYear()
    {
        // Get the first and last day of the current year
        $firstDayOfYear = Carbon::now()->startOfYear();
        $lastDayOfYear = Carbon::now()->endOfYear();

        // Generate an array of all months of the year (as numbers)
        $allMonthsOfYear = range(1, 12);

        // Query sales data and left join with the collection of all months
        $thisYearSales = collect($allMonthsOfYear)->map(function ($month) use ($firstDayOfYear) {
            $currentDate = $firstDayOfYear->copy()->month($month);

            $result = Sale::select(DB::raw("MONTH(sales_date) as month_number, COALESCE(SUM(total_amount), 0) as total_amount"))
                ->whereYear('sales_date', $currentDate->year)
                ->whereMonth('sales_date', $currentDate->month)
                ->groupBy('month_number')
                ->first();

            return $result ?: (object)['month_number' => $month, 'total_amount' => '0'];
        });

        return $this->sendSuccessResponse('success', Response::HTTP_OK, ["duration_by_year" => $thisYearSales]);
    }

    /********* start top performing **********/
    public function topPerforming(Request $request)
    {
        $type = $request->query('type');
        $month = $request->query('month');
        $year = $request->query('year');

        try {
            $result = "";
            if ( $type == "customer" ) {
                $result = $this->getTopCustomer($month, $year);
            } else {
                $result = $this->getTopProduct($month, $year);
            };
            
            $resource = new TopPerformingResource($result, $type);
            return $this->sendSuccessResponse('success', Response::HTTP_OK, $resource);
        } catch (Exception $e) {
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    private function getTopCustomer($month, $year)
    {
        $results = Sale::with('customer')->selectRaw('customer_id, sum(total_amount) as total_amount')
                    ->whereYear('sales_date', '=', $year)
                    ->whereMonth('sales_date', '=', $month)
                    ->groupBy('customer_id')
                    ->orderByDesc('total_amount')
                    ->take(5)
                    ->get();
        return $results;
    }

    private function getTopProduct($month, $year)
    {
        $results = SaleDetail::with('item')->selectRaw('item_id, sum(quantity) as total_amount')
            ->join('sales', 'sales.id', '=', 'sale_details.sale_id')
            ->whereYear('sales.sales_date', '=', $year)
            ->whereMonth('sales.sales_date', '=', $month)
            ->groupBy('item_id')
            ->orderByDesc('total_amount')
            ->take(5)
            ->get();

            return $results;
    }
    /******** end top performing ********/

    /******** start purchase analysis **********/
    public function purchaseAnalysis()
    {
        // Get the first and last day of the current year
        $firstDayOfYear = Carbon::now()->startOfYear();
        $lastDayOfYear = Carbon::now()->endOfYear();

        // Generate an array of all months of the year (as numbers)
        $allMonthsOfYear = range(1, 12);

        // Query sales data and left join with the collection of all months
        $thisYearSales = collect($allMonthsOfYear)->map(function ($month) use ($firstDayOfYear) {
            $currentDate = $firstDayOfYear->copy()->month($month);

            $result = Purchase::select(DB::raw("MONTH(purchase_date) as month_number, COALESCE(SUM(total_amount), 0) as total_amount"))
                ->whereYear('purchase_date', $currentDate->year)
                ->whereMonth('purchase_date', $currentDate->month)
                ->groupBy('month_number')
                ->first();

            return $result ?: (object)['month_number' => $month, 'total_amount' => '0'];
        });

        return $this->sendSuccessResponse('success', Response::HTTP_OK, ["purchase_analysis" => $thisYearSales]);
    }
    /******** End purchase analysis ********/

    /******* Get Sales Target Data *******/
    public function getSalesTargetData()
    {
        $getSalesTarget = SalesTarget::first();
        $result = "";
        if( $getSalesTarget ){
            if($getSalesTarget->target_type == 1) {
                $result = $this->getSalesTargetByProduct($getSalesTarget->target_period , $getSalesTarget->amount);
            }else{
                $result = $this->getSalesTargetBySalesAmount($getSalesTarget->target_period, $getSalesTarget->amount);
            }
        }else{
            return $this->sendErrorResponse('No data', Response::HTTP_BAD_REQUEST);
        }

        $resource = new SalesTargetResource($result);

        return $this->sendSuccessResponse('success', Response::HTTP_OK, $resource);
    }
    /******* End get Sales Target Data *******/

    /******* Create New Sales Target *******/
    public function createNewSalesTarget(NewSalesTargetRequest $request)
    {   
        try {
            DB::beginTransaction();
            $checkSalesTarget = SalesTarget::first();
            $validatedData = $request->validated();

            if($checkSalesTarget)
            {
                $checkSalesTarget->update([
                    "target_type" => $validatedData['target_type'],
                    "amount" => $validatedData['amount'],
                    "target_period" => $validatedData['target_period'],
                ]);
                $message = "Sales target is updated successfully.";
            }else{
                SalesTarget::create([
                    "target_type" => $validatedData['target_type'],
                    "amount" => $validatedData['amount'],
                    "target_period" => $validatedData['target_period'],
                ]);
                $message = "Sales target is created successfully.";
            }
            DB::commit();
            return $this->sendSuccessResponse($message, Response::HTTP_OK);
        } catch(Exception $e) {
            DB::rollBack();
            return $this->sendErrorResponse('Something Went Wrong', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    /******* End New Sales Target *******/
}
