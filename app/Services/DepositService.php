<?php

namespace App\Services;

use App\Helpers\Pipeline;
use App\Models\Deposit;
use App\Models\Mess;
use App\Models\MessUser;
use App\Models\Month;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class DepositService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Add a new deposit.
     *
     * @param array $data
     * @return Pipeline
     */
    public function addDeposit(array $data): Pipeline
    {
        try {
            $deposit = Deposit::create($data);

            // Notify about new deposit
            $this->notificationService->sendNotification([
                'user_id' => $deposit->messUser->user_id,
                'title' => 'New Deposit Added',
                'body' => "A deposit of ৳{$deposit->amount} has been added to your account",
                'type' => 'deposit_added',
                'extra_data' => [
                    'deposit_id' => $deposit->id,
                    'amount' => $deposit->amount,
                    'date' => $deposit->date
                ]
            ]);

            // Broadcast to admins
            $this->notificationService->sendNotification([
                'title' => 'New Deposit',
                'body' => "{$deposit->messUser->user->name} added a deposit of ৳{$deposit->amount}",
                'type' => 'deposit_added_admin',
                'is_broadcast' => true,
                'extra_data' => [
                    'deposit_id' => $deposit->id,
                    'amount' => $deposit->amount,
                    'date' => $deposit->date,
                    'user_id' => $deposit->messUser->user_id,
                    'user_name' => $deposit->messUser->user->name
                ]
            ]);

            return Pipeline::success(data: $deposit);
        } catch (\Exception $e) {
            Log::error('Failed to add deposit: ' . $e->getMessage());
            return Pipeline::error(message: "Failed to add deposit");
        }
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
        try {
            $oldAmount = $deposit->amount;
            $deposit->update($data);

            // Notify about deposit update
            $this->notificationService->sendNotification([
                'user_id' => $deposit->messUser->user_id,
                'title' => 'Deposit Updated',
                'body' => "Your deposit amount has been updated from ৳{$oldAmount} to ৳{$deposit->amount}",
                'type' => 'deposit_updated',
                'extra_data' => [
                    'deposit_id' => $deposit->id,
                    'old_amount' => $oldAmount,
                    'new_amount' => $deposit->amount,
                    'date' => $deposit->date
                ]
            ]);

            return Pipeline::success(message: 'Deposit updated successfully');
        } catch (\Exception $e) {
            Log::error('Failed to update deposit: ' . $e->getMessage());
            return Pipeline::error(message: "Failed to update deposit");
        }
    }

    /**
     * Delete a deposit.
     *
     * @param Deposit $deposit
     * @return Pipeline
     */
    public function deleteDeposit(Deposit $deposit): Pipeline
    {
        try {
            $amount = $deposit->amount;
            $userId = $deposit->messUser->user_id;
            $userName = $deposit->messUser->user->name;

            $deposit->delete();

            // Notify about deposit deletion
            $this->notificationService->sendNotification([
                'user_id' => $userId,
                'title' => 'Deposit Deleted',
                'body' => "A deposit of ৳{$amount} has been deleted from your account",
                'type' => 'deposit_deleted',
                'extra_data' => [
                    'amount' => $amount,
                    'date' => $deposit->date
                ]
            ]);

            return Pipeline::success(message: 'Deposit deleted successfully');
        } catch (\Exception $e) {
            Log::error('Failed to delete deposit: ' . $e->getMessage());
            return Pipeline::error(message: "Failed to delete deposit");
        }
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
