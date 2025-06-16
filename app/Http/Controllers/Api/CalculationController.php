<?php

namespace App\Http\Controllers\Api;
use App\Models\SavedCalculation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class CalculationController extends Controller
{

    public function calculateEmiLoan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'principal' => 'required|numeric|min:1',
            'annual_rate' => 'required|numeric|min:0',
            'tenure_years' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        $principal = $data['principal'];
        $annualRate = $data['annual_rate'];
        $tenureYears = $data['tenure_years'];

        $monthlyRate = ($annualRate / 100) / 12;
        $months = $tenureYears * 12;

        if ($monthlyRate == 0) {
            $emi = $principal / $months;
        } else {
            $emi = $principal * $monthlyRate * pow(1 + $monthlyRate, $months) / (pow(1 + $monthlyRate, $months) - 1);
        }

        $totalPayable = $emi * $months;
        $totalInterest = $totalPayable - $principal;

        // Prepare data for saving
        $inputData = [
            'principal' => $principal,
            'annual_rate' => $annualRate,
            'tenure_years' => $tenureYears,
        ];

        $resultData =[
            'emi' => round($emi, 2),
            'total_interest' => round($totalInterest, 2),
            'total_payable' => round($totalPayable, 2),
        ];

        // Save calculation to database
        $savedCalculation = SavedCalculation::create([
            'user_id' => auth()->id(),
            'type' => 'emi_loan',
            'name' => auth()->user()->name,
            'input_data' => $inputData,
            'result_data' => $resultData,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'EMI calculation successful and saved.',
            'data' => [
                'emi' => round($emi, 2),
                'total_interest' => round($totalInterest, 2),
                'total_payable' => round($totalPayable, 2),
            ]
        ]);
    }

    // Future Value Calculation
    public function calculateFutureValue(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'present_value' => 'required|numeric|min:1',
            'annual_rate' => 'required|numeric|min:0',
            'tenure_years' => 'required|integer|min:1',
            'compounding_frequency' => 'required|integer|min:1',
        ], [
            // validation messages
            'name.required' => 'Please provide a name to save this calculation.',
            'name.string' => 'Name must be a string.',
            'name.max' => 'Name cannot be longer than 255 characters.',
            // other messages as before
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        $presentValue = $data['present_value'];
        $annualRate = $data['annual_rate'];
        $tenureYears = $data['tenure_years'];
        $compoundingFrequency = $data['compounding_frequency'];

        $ratePerPeriod = ($annualRate / 100) / $compoundingFrequency;
        $totalPeriods = $tenureYears * $compoundingFrequency;

        $futureValue = $presentValue * pow(1 + $ratePerPeriod, $totalPeriods);

        // Prepare data for saving
        $inputData = [
            'present_value' => $presentValue,
            'annual_rate' => $annualRate,
            'tenure_years' => $tenureYears,
            'compounding_frequency' => $compoundingFrequency,
        ];

        $resultData = [
            'future_value' => round($futureValue, 2),
        ];

        // Save calculation
        $savedCalculation = SavedCalculation::create([
            'user_id' => auth()->id(),
            'type' => 'future_value',
            'name' => auth()->user()->name,
            'input_data' => $inputData,
            'result_data' => $resultData,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Future value calculation successful and saved.',
            'data' => [
                'calculation_id' => $savedCalculation->id,
                'future_value' => round($futureValue, 2),
            ]
        ]);
    }

    // Save Calculation
    public function saveCalculation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string',
            'name' => 'required|string',
            'input_data' => 'required|json',
            'result_data' => 'required|json',
        ], [
            'type.required' => 'Calculation type is required.',
            'type.string' => 'Calculation type must be a valid string.',

            'name.required' => 'Name for this calculation is required.',
            'name.string' => 'Calculation name must be a string.',

            'input_data.required' => 'Input data is required.',
            'input_data.json' => 'Input data must be a valid JSON.',

            'result_data.required' => 'Result data is required.',
            'result_data.json' => 'Result data must be a valid JSON.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        $savedCalculation = SavedCalculation::create([
            'user_id' => auth()->id(),
            'type' => $data['type'],
            'name' => $data['name'],
            'input_data' => $data['input_data'],
            'result_data' => $data['result_data'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Calculation saved successfully.',
            'data' => [
                'type' => $savedCalculation->type,
                'name' => $savedCalculation->name,
                'input_data' => json_decode($savedCalculation->input_data),
                'result_data' => json_decode($savedCalculation->result_data),
                'created_at' => $savedCalculation->created_at->toDateTimeString()
            ]
        ], 201);
    }

    // List Saved Calculations
    public function listSavedCalculations()
    {
        $savedCalculations = SavedCalculation::where('user_id', auth()->id())->get();

        return response()->json([
            'success' => true,
            'message' => 'Saved calculations retrieved successfully.',
            'data' => $savedCalculations
        ]);
    }

    // Delete Saved Calculation
    public function deleteSavedCalculation($id)
    {
        $savedCalculation = SavedCalculation::findOrFail($id);

        if ($savedCalculation->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete this calculation.'
            ], 403);
        }

        $savedCalculation->delete();

        // 204 No Content usually returns no body, but you can send a message if you want
        return response()->json([
            'success' => true,
            'message' => 'Saved calculation deleted successfully.'
        ], 200);
    }

}
