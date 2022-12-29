<?php

namespace App\Http\Controllers;

use App\Models\Fund;
use Illuminate\Http\Request;
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

            $this->authorize($id);

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

            $this->authorize($fund->id);
    
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

            $this->authorize($fund->id);
    
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

            $this->authorize($fund->id);
    
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
    public static function setNotes(Request $request)
    {
        try {

            $validated = $request->validate([
                'notes' => 'required',
                'id' => 'required', 
            ]);
            
            $fund = Fund::find($validated['id']);

            $this->authorize($fund->id);
    
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

    /**
     * Deposit funds (POST).
     */
    public static function deposit()
    {
        // header('Access-Control-Allow-Origin: *');
        $data = json_decode(file_get_contents("php://input"));
        if (!isset($data->depositedAmount, $data->depositedTo, $data->depositSource) || !is_numeric($data->depositedAmount)) return false;

        $depositNotes = isset($data->notes) ? $data->notes : "";
        $result = false;
        if (strval($data->depositedTo) != "all") {
            // deposit to a specific fund
            $fund = Fund::find(intval($data->depositedTo));
            if($fund['userId'] != auth()->user()->id) {
                http_response_code(403);
                return false;
            }
            $result = Fund::deposit(intval($data->depositedTo), $data->depositedAmount, $data->depositSource, $depositNotes, auth()->user()->id);
        } else {
            // Deposits to all funds of auth()->user()->id
            $result = Fund::depositToAll($data->depositedAmount, $data->depositSource, $depositNotes, auth()->user()->id);
        }

        header('Content-Type: application/json');
        echo json_encode(["result" => $result === false ? "Failed." : $result]);
    }

    /**
     * Withdraw funds (POST).
     */
    public static function withdraw()
    {
        // header('Access-Control-Allow-Origin: *');
        $data = json_decode(file_get_contents("php://input"));
        if (!isset($data->withdrawnAmount) || !is_numeric($data->withdrawnAmount) || !isset($data->withdrawnFrom)) return false;

        $withdrawalNotes = isset($data->notes) ? $data->notes : "";
        $withdrawalReason = isset($data->withdrawalReason) ? $data->withdrawalReason : "";

        $fund = Fund::find(intval($data->withdrawnFrom));
        if($fund['userId'] != auth()->user()->id) {
            http_response_code(403);
            return false;
        }
        $result = Fund::withdraw(intval($data->withdrawnFrom), $data->withdrawnAmount, $withdrawalReason, $withdrawalNotes, auth()->user()->id);

        header('Content-Type: application/json');
        echo json_encode(["result" => $result === false ? "Failed." : $result]);
    }

    // public static function setBalance($id)
    // {
    // //     header('Access-Control-Allow-Origin: *');
    //     $data = json_decode(file_get_contents("php://input"));
    //     if (!isset($data->balance)) return false;
    //     $result = Fund::setBalance($id, $data->balance);

    //     header('Content-Type: application/json');
    //     echo json_encode(["result" => $result === false ? "Failed." : $result]);
    // }

    /**
     * Transfer funds (POST)
     */
    public static function transfer(): void
    {
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
     * Export the funds data (with withdrawals and deposits) in to file(s).
     */
    public static function export()
    {


        $userId = auth()->user()->id;

        $funds = Fund::whereUserIdIs($userId);
        $withdrawals = Withdrawal::whereUserIdIs($userId);
        $deposits = Deposit::whereUserIdIs($userId);

        $currentDateTime = date("Y") . "-" . date("m") . "-" . date("d") . "_" . date("H") . "-" . date("i") . "-" . date("s");
        $fundsFileName = "funds_$currentDateTime";
        $withdrawalsFileName = "withdrawals_$currentDateTime";
        $depositsFileName = "deposits_$currentDateTime";

        file_put_contents($fundsFileName, json_encode($funds));
        file_put_contents($withdrawalsFileName, json_encode($withdrawals));
        file_put_contents($depositsFileName, json_encode($deposits));
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

    private function authorize($fundId) {
        $fund = Fund::find($fundId);
        if($fund && $fund->user_id == auth()->user()->id) return true;
        throw new \Exception('Forbidden');
    }
}
