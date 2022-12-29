# funds-manager-backend

## Description

- A Laravel RESTful API for a funds-manager application (Laravel 9).
- Uses JWT for authentication.
- Uses a MySQL database.
### Installation and Running

Run the command `git clone <repo>`
and start using the API.

## How to use this API
### The End-Points
- Get all funds (GET): /api/fund/index
- Get a single fund by id (GET): /api/fund/index/readSingle/{id}
- Store a fund (POST): /api/fund/store
- Deposit funds (POST): /api/fund/deposit
- Withdraw funds (POST): /api/fund/withdraw
- Get deposits history (GET): /api/fund/getDepositHistory/all
- Get withdrawals history (GET): /api/fund/getWithdrawalsHistory/all
- Get deposits history for a fund by id (GET): /api/fund/getDepositsHistory/{id}
- Get withdrawals history for fund by id (GET): /api/fund/getWithdrawalsHistory/{id}
- Update a fund's name (POST): /api/fund/setFundName
- Update a fund's percentage (POST): /api/fund/setFundPercentage
- Update a fund's size (POST): /api/fund/setSize
- Update a fund's notes (POST): /api/fund/setNotes