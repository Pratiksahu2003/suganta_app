# Wallet API Documentation (v1)

The Wallet API allows users to view their current balance, total earnings, and a detailed history of all transactions (credits and debits).

## Base URL
`/api/v1/wallet`

## Authentication
All endpoints require **auth:sanctum** token.

---

## 1. Get Wallet Overview & Transactions
Returns the user's wallet balance and a paginated list of transactions.

| Attribute | Value |
|-----------|-------|
| **Method** | `GET` |
| **Endpoint** | `/api/v1/wallet` |
| **Access** | Protected |

### Query Parameters
| Parameter | Type | Description |
|-----------|------|-------------|
| `page` | Integer | Pagination page number |
| `per_page` | Integer | Items per page (max 50) |
| `type` | String | Filter by `credit` or `debit` |
| `transaction_type` | String | Filter by `marketplace_sale`, `withdrawal`, etc. |

### Success Response (200)
```json
{
    "status": "success",
    "message": "Wallet information retrieved successfully.",
    "data": {
        "wallet": {
            "balance": 450.00,
            "total_earned": 500.00,
            "total_withdrawn": 50.00,
            "currency": "INR"
        },
        "transactions": {
            "data": [
                {
                    "id": 1,
                    "type": "credit",
                    "amount": 450.00,
                    "balance_before": 0.00,
                    "balance_after": 450.00,
                    "transaction_type": "marketplace_sale",
                    "description": "Sale of listing: Class 10 Math Notes",
                    "reference_id": 123,
                    "reference_type": "App\\Models\\MarketplaceOrder",
                    "created_at": "2026-04-02T13:45:00.000000Z"
                }
            ],
            "meta": {
                "current_page": 1,
                "last_page": 1,
                "per_page": 15,
                "total": 1
            },
            "links": {
                "next": null,
                "prev": null
            }
        }
    }
}
```

---

## 2. Transaction Types
Common transaction types recorded in the wallet:

- `marketplace_sale`: Earnings from selling items in the [**Marketplace**](file:///c:/Users/NXTGN/Desktop/SuGanta_API/docs/MarketplaceApiV6.md) (90% payout).
- `withdrawal`: Manual or automatic withdrawal to bank/UPI.
- `refund`: Reversal of a transaction.
- `referral_bonus`: Credits for successful referrals.

---

> [!NOTE]
> All amounts are represented as decimals in the system's base currency (INR).
