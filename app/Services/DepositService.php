<?php

namespace App\Services;

use App\Helpers\Pipeline;
use App\Models\Deposit;
use App\Models\MessUser;
use App\Models\Month;

class DepositService
{
    /**
     * Add a new deposit.
     *
     * @param array $data
     * @return Pipeline
     */
    public function addDeposit(array $data): Pipeline
    {
        $deposit = Deposit::create($data);
        return Pipeline::success(data: $deposit);
    }

    /**
     * Update an existing deposit.
     *
     * @param Deposit $deposit
     * @param array $data
     * @return Pipeline
     */
    public function updateDeposit(Deposit $deposit, array $data): Pipeline
    {
        $deposit->update($data);
        return Pipeline::success(data: $deposit->fresh());
    }

    /**
     * Delete a deposit.
     *
     * @param Deposit $deposit
     * @return Pipeline
     */
    public function deleteDeposit(Deposit $deposit): Pipeline
    {
        $deposit->delete();
        return Pipeline::success(message: 'Deposit deleted successfully');
    }

    /**
     * Get a list of deposits.
     *
     * @param Month $month
     * @return Pipeline
     */
    public function listDeposits(Month $month): Pipeline
    {
        $deposits = Deposit::selectRaw('mess_user_id, MAX(date) as latest_date, SUM(amount) as total_amount')
            ->with("messUser.user")
            ->where('month_id', $month->id)
            ->groupBy('mess_user_id')
            ->orderBy('latest_date', 'desc')
            ->get();

        $totalAmount = $deposits->sum('total_amount');

        $data = [
            'deposits' => $deposits,
            'total_amount' => $totalAmount,
        ];

        return Pipeline::success(data: $data);
    }

    function listDepositHistory(Month $month, MessUser $messUser) : Pipeline {
        $deposits = $month->deposits()->where("mess_user_id", $messUser->id)->orderByDesc("created_at")->get();
        $totalAmount = $deposits->sum("amount");
        $data = [
            'deposits' => $deposits,
            'total_amount' => $totalAmount,
        ];

        return Pipeline::success(data: $data);
    }
}
