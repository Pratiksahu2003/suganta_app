# Marketplace API (V6)

This documentation provides details for the SuGanta Marketplace API (Version 6). The marketplace allows users to buy and sell products (Soft Copies via payment, Hard Copies via chat interaction).

**Base path**: `/api/marketplace`  
**Auth**: All endpoints require a Bearer token (Laravel Sanctum).

---

## Endpoints Summary

### Discovery (Authenticated)
| Method | Endpoint | Description | Access | 
|--------|----------|-------------|--------|
| GET | `/listings` | List all active listings (paginated) | Auth |
| GET | `/listings/{id}` | Get listing detail | Auth |
| GET | `/trending` | Get top trending listings (Redis) | Auth |
| GET | `/plans` | Get marketplace subscription plans | Auth |

### Buyer Interactions (Authenticated)
| Method | Endpoint | Description | Access |
|--------|----------|-------------|--------|
| POST | `/listings/{id}/purchase` | Initiate soft-copy purchase (Cashfree) | Auth |
| POST | `/listings/{id}/contact` | Contact seller for hard-copy (Chat) | Auth |
| GET | `/listings/{id}/download` | Request secure download path | Auth |

### Seller Management (Authenticated)
| Method | Endpoint | Description | Access |
|--------|----------|-------------|--------|
| GET | `/my-listings` | List authenticated user's listings | Auth |
| POST | `/my-listings` | Create a new listing | Auth |
| PUT | `/my-listings/{id}` | Update an existing listing | Auth |
| DELETE | `/my-listings/{id}` | Remove a listing | Auth |

---

## Wallet & Commissions

The marketplace implements an automated wallet-based payout system for **Soft Copy** transactions:

1. **Platform Commission**: A **10% commission** is automatically deducted from the total listing price upon a successful purchase.
2. **Automated Payout**: The remaining **90%** (Seller Amount) is instantly credited to the seller's SuGanta Wallet.
3. **Transparency**: Both the buyer's total payment and the seller's net credit are recorded in the `MarketplaceOrder` and `WalletTransaction` records.

> [!TIP]
> Sellers can track their automated payouts and current balance using the [**Wallet API (v1)**](file:///c:/Users/NXTGN/Desktop/SuGanta_API/docs/WalletApi.md).

> [!NOTE]
> For **Hard Copy** listings, the platform does not handle payments. Users are expected to negotiate and complete transactions independently via the integrated chat system.

---

## 1. List All Listings
Returns a paginated list of active marketplace listings.

| | |
|---|---|
| **Endpoint** | `GET /api/marketplace/listings` |
| **Access** | Protected (auth:sanctum) |

### Query Parameters
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| category | string | — | Filter by category |
| type | string | — | Filter by type: `soft`, `hard` |
| search | string | — | Search in title |
| page | integer | 1 | Page number |

### Success Response (200)
```json
{
  "status": "success",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "user_id": 5,
        "title": "Study Notes 2024",
        "description": "Comprehensive notes for semester 2.",
        "price": "299.00",
        "category": "Education",
        "type": "soft",
        "thumbnail": "https://...",
        "images": ["url1", "url2", "url3", "url4"],
        "views_count": 120,
        "user": {
          "id": 5,
          "name": "John Doe",
          "profile_image": "https://..."
        }
      }
    ],
    "total": 50
  }
}
```

---

## 2. Get Listing Detail
Returns full details of a specific active listing. Increments the view count.

| | |
|---|---|
| **Endpoint** | `GET /api/marketplace/listings/{id}` |
| **Access** | Protected (auth:sanctum) |

### Success Response (200)
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "title": "...",
    "status": "active",
    "user": {
      "id": 5,
      "name": "..."
    }
  }
}
```

---

## 3. Trending Listings
Fetch top 10 trending items based on real-time Redis interactions.

| | |
|---|---|
| **Endpoint** | `GET /api/marketplace/trending` |
| **Access** | Protected (auth:sanctum) |

---

## 4. Marketplace Plans
Returns available subscription plans for sellers.

| | |
|---|---|
| **Endpoint** | `GET /api/marketplace/plans` |
| **Access** | Protected (auth:sanctum) |

---

## 5. Purchase Soft Copy
Initiates a payment session using Cashfree for digital listings.

| | |
|---|---|
| **Endpoint** | `POST /api/marketplace/listings/{id}/purchase` |
| **Access** | Protected (auth:sanctum) |

This endpoint generates a **Cashfree Checkout URL**. After the user completes payment:
1. **Commission (10%)** is subtracted from the amount.
2. **Seller payout (90%)** is credited to their wallet.
3. **MarketplaceOrder** record is generated with a unique `download_token`.

### Success Response (200)
```json
{
  "status": "success",
  "checkout_url": "https://payments.cashfree.com/..."
}
```

> [!TIP]
> After payment completion, users should be redirected back to the app to download their purchase. Download tokens are valid for 5 minutes.

---

## 6. Contact Seller (Hard Copy)
Initiates a real-time chat with the seller for physical listings and sends an interest message.

| | |
|---|---|
| **Endpoint** | `POST /api/marketplace/listings/{id}/contact` |
| **Access** | Protected (auth:sanctum) |

### Success Response (200)
```json
{
  "status": "success",
  "message": "Conversation initiated.",
  "conversation_id": 150
}
```

---

## 7. Secure Download
Secure download link retrieval for purchased soft copies. Requires a `token` (usually provided after successful payment redirect).

| | |
|---|---|
| **Endpoint** | `GET /api/marketplace/listings/{id}/download` |
| **Access** | Protected (auth:sanctum) |

### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| token | string | Yes | Redis temporary download token |

---

## 8. Create Listing
Create a new marketplace listing. Use `POST` with JSON payload.

| | |
|---|---|
| **Endpoint** | `POST /api/marketplace/my-listings` |
| **Access** | Protected (auth:sanctum) |

### Request Body
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| title | string | Yes | Listing title (max 255) |
| description | string | Yes | Detailed description |
| price | numeric | Yes | Cost in INR (min 0) |
| type | string | Yes | `soft` or `hard` |
| file_path | string | Required if type=soft | S3/Cloud Storage path for file |
| category | string | No | Listing category |
| thumbnail| string | No | URL to thumbnail image |
| images | array | Yes | Exactly 4 to 6 image URLs |

### Success Response (201)
```json
{
  "status": "success",
  "data": { "id": 102, "title": "New Item", ... }
}
```

---

## 9. Update Listing
Modify your existing listing.

| | |
|---|---|
| **Endpoint** | `PUT /api/marketplace/my-listings/{id}` |
| **Access** | Protected (auth:sanctum) |

### Request Body (Optional Fields)
| Field | Type | Description |
|-------|------|-------------|
| title | string | Update title |
| description | string | Update description |
| price | numeric | Update price |
| status | string | `active`, `sold`, `inactive` |
| images | array | Update image set |

---

## 10. Remove Listing
Remove your listing from the marketplace.

| | |
|---|---|
| **Endpoint** | `DELETE /api/marketplace/my-listings/{id}` |
| **Access** | Protected (auth:sanctum) |

---

## Data Reference

### Listing Model
| Field | Type | Description |
|-------|------|-------------|
| id | integer | Unique ID |
| title | string | Name of listing |
| description | string | Long description |
| price | decimal | Price in INR |
| category | string | Category tag |
| type | string | `soft` (downloadable) or `hard` (physical) |
| thumbnail | string | URL of main image |
| images | array | List of secondary image URLs |
| status | string | `active`, `sold`, `inactive` |
| views_count | integer | Total view statistics |

### Listing Order Model
| Field | Type | Description |
|-------|------|-------------|
| id | integer | Unique ID |
| user_id | integer | Buyer's user ID |
| listing_id | integer | Purchased listing ID |
| amount | decimal | Total price paid by buyer |
| commission_amount | decimal | Platform commission (10%) |
| seller_amount | decimal | Net payout credited to seller's wallet |
| status | string | `pending`, `completed`, `failed` |
| created_at | datetime | Purchase timestamp |
