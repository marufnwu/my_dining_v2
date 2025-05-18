<?php

namespace App\Services;

use App\Helpers\Pipeline;
use App\Models\Deposit;
use App\Models\Mess;
use App\Models\MessUser;
use App\Models\Month;
use App\Models\User;

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

    function listDepositHistory(Month $month, MessUser $messUser): Pipeline
    {
        $deposits = $month->deposits()->where("mess_user_id", $messUser->id)
            ->with("messUser.user")->orderByDesc("created_at")->get();
        $totalAmount = $deposits->sum("amount");
        $data = [
            'deposits' => $deposits,
            'total_amount' => $totalAmount,
        ];

        return Pipeline::success(data: $data);
    }

    /**
     * Get total deposits in a specific month for a mess.
     *
     * @param Month $month
     * @param Mess $mess
     * @return Pipeline
     */
    public function getTotalDepositInMonth(Month $month, Mess $mess): Pipeline
    {
        $total = $mess->months()->where("id", operator: $month->id)->deposits()->sum("amount");

        return Pipeline::success(data: $total);

    }
    /**
     * Get total deposits in a specific month for a mess.
     *
     * @param Month $month
     * @param Mess $mess
     * @return Pipeline
     */
    public function getTotalDepositInMonthByUser(Month $month,  MessUser $messUser): Pipeline
    {
        $total = $month
            ->deposits()
            ->where('mess_user_id', $messUser->id)
            ->sum('amount');

        return Pipeline::success(data: $total);

    }


    /**
     * Get total deposit amount for a month
     *
     * @param Month $month
     * @return Pipeline
     */
    public function getTotalDeposit(Month $month): Pipeline
    {
        $totalDeposit = $month->deposits()->sum('amount');

        return Pipeline::success(data: $totalDeposit);
    }

    /**
     * Get deposit amount for a specific user in a month
     *
     * @param Month $month
     * @param MessUser $messUser
     * @return Pipeline
     */
    public function getUserDeposit(Month $month, MessUser $messUser): Pipeline
    {
        $totalDeposit = $month->deposits()
            ->where('mess_user_id', $messUser->id)
            ->sum('amount');

        return Pipeline::success(data: $totalDeposit);
    }



}
