<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\Fund;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Auth;

class FundController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Send all funds in JSON.
     */
    public function index()
    {
        try {
            $funds = Fund::where('user_id', '=', auth()->user()->id)->get();

            return json_encode(['data' => $funds]);
        } catch(\Exception $e) {
            return json_encode(['message' => 'failed', 'data' => $e->getMessage()]);
        }

    }

    public function show($id)
    {
        try {
            $fund = Fund::find($id);

            $this->authorizeFundAccess($id);

            return json_encode(['data' => $fund()]);
        } catch(\Exception $e) {
            return json_encode(['message' => 'failed', 'data' => $e->getMessage()]);
        }

    }

    /**
     * Store a new fund.
     */
    public function store(Request $request)
    {
        try {

            $validated = $request->validate([
                'fundName' => 'required|unique:funds,fundName',
                'fundPercentage' => 'required', 
            ]);
            
            //extra validations
            $allFunds = Fund::where('user_id', '=', auth()->user()->id)->get();
            $totalPercentages = 0.0;
            foreach ($allFunds as $f) {
                $totalPercentages += $f->fundPercentage;
            }
            if ($totalPercentages + $validated['fundPercentage'] > 100) {
                throw new \Exception('Invalid percentage');
            }
            
            $fund = new Fund;
    
            $fund->fundName = $validated['fundName'];
            $fund->fundPercentage = $validated['fundPercentage'];
            $fund->balance = isset($request['balance']) ? $request['balance']: 0.0;
            $fund->size = isset($request['size']) ? $request['size'] : 'Open';
            $fund->notes = isset($request['notes']) ? $request['notes'] : '';
            $fund->user_id = auth()->user()->id;
    

            if($fund->save()) {
                return json_encode(['data' => $fund]);

            } else throw new \Exception('failed to create fund');
    
        } catch(\Exception $e) {
            return json_encode(['message' => 'failed', 'data' => $e->getMessage()]);
        }
    }

    public function setFundName(Request $request)
    {
        try {

            $validated = $request->validate([
                'fundName' => 'required|unique:funds,fundName',
                'id' => 'required', 
            ]);
            
            $fund = Fund::find($validated['id']);

            $this->authorizeFundAccess($fund->id);
    
            $fund->fundName = $validated['fundName'];
            if($fund->save()) {
                return json_encode(['data' => $fund]);

            } else throw new \Exception('failed to update fund');
    
        } catch(\Exception $e) {
            return json_encode(['message' => 'failed', 'data' => $e->getMessage()]);
        }
    }
    public function setFundPercentage(Request $request)
    {
        try {

            $validated = $request->validate([
                'fundPercentage' => 'required|min:0|numeric',
                'id' => 'required', 
            ]);
            
            $fund = Fund::find($validated['id']);

            $this->authorizeFundAccess($fund->id);
    
            //extra validations
            $allFunds = Fund::where('user_id', '=', auth()->user()->id)->where('id', '<>', $fund->id)->get();
            $totalPercentages = 0.0;
            foreach ($allFunds as $f) {
                $totalPercentages += $f->fundPercentage;
            }
            if ($totalPercentages + $validated['fundPercentage'] > 100) {
                throw new \Exception('Invalid percentage value (' . $validated['fundPercentage'] . ')');
            }


            $fund->fundPercentage = $validated['fundPercentage'];
            if($fund->save()) {
                return json_encode(['data' => $fund]);

            } else throw new \Exception('failed to update fund');
    
        } catch(\Exception $e) {
            return json_encode(['message' => 'failed', 'data' => $e->getMessage()]);
        }
    }
    public function setSize(Request $request)
    {
        try {

            $validated = $request->validate([
                'size' => 'required',
                'id' => 'required', 
            ]);
            
            $fund = Fund::find($validated['id']);

            $this->authorizeFundAccess($fund->id);
    
            //extra validations
            if (is_numeric($validated['size']) && $validated['size'] <= 0) {
                throw new \Exception('Invalid size value (' . $validated['size'] . ')');
            }

            $fund->size = $validated['size'];
            if($fund->save()) {
                return json_encode(['data' => $fund]);

            } else throw new \Exception('failed to update fund');
    
        } catch(\Exception $e) {
            return json_encode(['message' => 'failed', 'data' => $e->getMessage()]);
        }
    }
    public function setNotes(Request $request)
    {
        try {

            $validated = $request->validate([
                'notes' => 'required',
                'id' => 'required', 
            ]);
            
            $fund = Fund::find($validated['id']);

            $this->authorizeFundAccess($fund->id);
    
            $fund->notes = $validated['notes'];
            if($fund->save()) {
                return json_encode(['data' => $fund]);

            } else throw new \Exception('failed to update fund');
    
        } catch(\Exception $e) {
            return json_encode(['message' => 'failed', 'data' => $e->getMessage()]);
        }
    }

    /**
     * Deposit funds (POST).
     */
    public function deposit(Request $request)
    {
        try {
            $validated = $request->validate([
                'depositedAmount' => 'required|min:0|numeric',
                'depositedTo' => 'required',
                'depositSource' => 'required',
                'notes' => ''
            ]);

            $this->makeDepositUpdateFund($validated);

            return true;

        } catch(\Exception $e) {
            return json_encode(['message' => 'failed', 'data' => $e->getMessage()]);
        }
    }

    /**
     * Withdraw funds (POST).
     */
    public function withdraw(Request $request)
    {
        try {
            $validated = $request->validate([
                'withdrawnAmount' => 'required|min:0|numeric',
                'withdrawnFrom' => 'required|integer',
                'withdrawalReason' => '',
                'notes' => ''
            ]);

            $this->makeWithdrawalUpdateFund($validated);

            return true;

        } catch(\Exception $e) {
            return json_encode(['message' => 'failed', 'data' => $e->getMessage()]);
        }
    }

    public static function getDepositsHistory($for)
    {
        // header('Access-Control-Allow-Origin: *');
        if ($for == "all") {
            $depositsHistory = Deposit::whereUserIdIs(auth()->user()->id);
            header('Content-Type: application/json');
            echo json_encode($depositsHistory !== false ? $depositsHistory : []);
            return;
        } else {
            $fund = Fund::find(intval($for));
            if($fund['userId'] != auth()->user()->id) {
                http_response_code(403);
                return false;
            }
            $depositsHistoryForFund = Deposit::whereFundIdIs($for);
            header('Content-Type: application/json');
            echo json_encode($depositsHistoryForFund !== false ? $depositsHistoryForFund : []);
            return;
        }
    }

    public static function getWithdrawalsHistory($for)
    {
        // header('Access-Control-Allow-Origin: *');
        if ($for == "all") {
            $withdrawalsHistory = Withdrawal::whereUserIdIs(auth()->user()->id);
            header('Content-Type: application/json');
            echo json_encode($withdrawalsHistory !== false ? $withdrawalsHistory : []);
        } else {
            $fund = Fund::find(intval($for));
            if($fund['userId'] != auth()->user()->id) {
                http_response_code(403);
                return false;
            }
            $withdrawalsHistoryForFund = Withdrawal::whereFundIdIs($for);
            header('Content-Type: application/json');
            echo json_encode($withdrawalsHistoryForFund !== false ? $withdrawalsHistoryForFund : []);
            return;
        }
    }
    public static function getWithdrawalById($id)
    {
        // header('Access-Control-Allow-Origin: *');

        $withdrawal = Withdrawal::find($id);
        if ($withdrawal !== false) {
            if($withdrawal['userId'] != auth()->user()->id) {
                http_response_code(403);
                return false;
            }
            header('Content-Type: application/json');
            echo json_encode($withdrawal);
        } else {
            return false;
        }
    }

    public static function setWithdrawalNotes()
    {

        // header('Access-Control-Allow-Origin: *');

        $data = json_decode(file_get_contents("php://input"));
        if (!isset($data->id)) return false;
        $id = intval($data->id);

        $withdrawal = Withdrawal::find($id);
        if($withdrawal['userId'] != auth()->user()->id) {
            http_response_code(403);
            return false;
        }

        $result = Withdrawal::setNotes($id, $data->notes);

        header('Content-Type: application/json');
        echo json_encode(["result" => $result === false ? "Failed." : $result]);
    }
    public static function getDepositById($id)
    {
        // header('Access-Control-Allow-Origin: *');

        $deposit = Deposit::find($id);
        if ($deposit !== false) {
            if($deposit['userId'] != auth()->user()->id) {
                http_response_code(403);
                return false;
            }
    
            header('Content-Type: application/json');
            echo json_encode($deposit);
        } else {
            return false;
        }
    }

    public static function setDepositNotes()
    {
       // header('Access-Control-Allow-Origin: *');

        $data = json_decode(file_get_contents("php://input"));
        if (!isset($data->id)) return false;
        $id = intval($data->id);

        $deposit = Deposit::find($id);
        if($deposit['userId'] != auth()->user()->id) {
            http_response_code(403);
            return false;
        }

        $result = Deposit::setNotes($id, $data->notes);

        header('Content-Type: application/json');
        echo json_encode(["result" => $result === false ? "Failed." : $result]);
    }

    /**
     * Delete an existing fund.
     */
    public static function delete($id): void
    {
        // header('Access-Control-Allow-Origin: *');
        $result = Fund::delete($id);

        header('Content-Type: application/json');
        echo json_encode(["result" => $result === false ? "Failed." : "Successfully Deleted Fund."]);
    }

    private function authorizeFundAccess($fundId) {
        $fund = Fund::find($fundId);
        if($fund && $fund->user_id == auth()->user()->id) return true;
        throw new \Exception('Forbidden');
    }

    private function makeDepositUpdateFund($validated) {
        if (strval($validated['depositedTo']) != 'all') {
            // deposit to a specific fund
            $this->addToFundBalance(intval($validated['depositedTo']), $validated['depositedAmount']);
            $this->logDeposit($validated['depositSource'], intval($validated['depositedTo']), $validated['depositedAmount'], isset($validated['notes']) ?? '');

        } else {
            $allFunds = Fund::where('user_id', '=', auth()->user()->id)->get();
            if ($allFunds === false) {
                throw new \Exception('Could not fetch funds for update.');
            }
            foreach ($allFunds as $fund) {
                $addedBalance = floatval($fund->fundPercentage / 100) * floatval($validated['depositedAmount']);
    
                $this->addToFundBalance($fund->id, $addedBalance);
    
            }
        }
    }
    private function addToFundBalance($fundId, $addedAmount) {
        $fund = Fund::find($fundId);

        $this->authorizeFundAccess($fund->id);

        $fund->balance = floatval($fund->balance) + floatval(abs($addedAmount));
        $fund->totalDeposits = floatval($fund->totalDeposits) + floatval(abs($addedAmount));
        $fund->lastDeposit = date("Y") . date("m") . date("d") . date("H") . date("i") . date("s");

        if($fund->save()) {
            return true;
        } 
        else throw new \Exception('Failed to update fund.');
    }
    private function logDeposit($depositSource, $id, $depositedAmount, $depositNotes = '') {
        $deposit = new Deposit;
        $deposit->depositSource = $depositSource;
        $deposit->depositedTo = $id;
        $deposit->depositedAmount = $depositedAmount;
        $deposit->user_id = auth()->user()->id;
        $deposit->notes = $depositNotes;

       if($deposit->save()) return true;
       throw new \Exception('Failed to log deposit.');

    }

    private function makeWithdrawalUpdateFund($validated) {
        $this->deductFromFundBalance($validated['withdrawnFrom'], $validated['withdrawnAmount']);
        $this->logWithdrawal($validated['withdrawnFrom'], $validated['withdrawnAmount'], isset($validated['withdrawalReason']) ?? '', isset($validated['notes']) ?? '');
    }
    private function deductFromFundBalance($fundId, $deductedAmount) {
        $fund = Fund::find($fundId);
        $this->authorizeFundAccess($fund->id);

        $fund->balance = floatval($fund->balance) - floatval(abs($deductedAmount));

        if($fund->balance < 0) throw new \Exception('Insufficient funds.');

        $fund->totalWithdrawals = floatval($fund->totalWithdrawals) + floatval(abs($deductedAmount));
        $fund->lastWithdrawal = date("Y") . date("m") . date("d") . date("H") . date("i") . date("s");

        if($fund->save()) {
            return true;
        } 
        else throw new \Exception('Failed to update fund.');
    }
    private function logWithdrawal($withdrawnFrom, $withdrawnAmount, $withdrawalReason = '', $notes = '') {
        $withdrawal = new Withdrawal;
        $withdrawal->withdrawnAmount = $withdrawnAmount;
        $withdrawal->withdrawnFrom = $withdrawnFrom;
        $withdrawal->withdrawalReason = $withdrawalReason;
        $withdrawal->notes = $notes;
        $withdrawal->user_id = auth()->user()->id;

        if($withdrawal->save()) return true;
       throw new \Exception('Failed to log withdrawal.');
    }
}
