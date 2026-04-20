# Assignment 3 – Developer Documentation

## 1. Overview

This API provides authenticated access to mail messages using JWT authentication, role-based access control, request ID logging, rate limiting, and centralized error handling.

---

## 2. Authentication

### 2.1 Auth Method

- Scheme: Bearer token (JWT)
- How to obtain a token:
  - Endpoint: `POST /auth/login`
  - Request body format:
    ```json
    {
      "username": "user1",
      "password": "user123"
    }
    ```
  - Example success response:
    ```json
    {
      "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
    }
    ```
- Token contents include at least:
  - `userId`
  - `role`
- Token expiry: `1h`

### 2.2 Using the Token

Authenticated endpoints require this header:

```http
Authorization: Bearer <token>
```

If the token is missing, invalid, or expired, the API returns a `401 Unauthorized` error in the standard JSON error format.

---

## 3. Roles & Access Rules

- `admin`
  - Can view any mail message.
- `user`
  - Can view only their own mail messages.

| Endpoint     | Method | admin           | user              |
|--------------|--------|-----------------|-------------------|
| `/auth/login`| POST   | ✅              | ✅                |
| `/status`    | GET    | ✅              | ✅                |
| `/mail/:id`  | GET    | ✅ any mail     | ✅ own mail only  |

---

## 4. Endpoints

### 4.1 `POST /auth/login`

**Description:**  
Authenticate with username and password to receive a JWT.

**Request Body:**

```json
{
  "username": "user1",
  "password": "user123"
}
```

**Success Response (200):**

```json
{
  "token": "..."
}
```

**Common Failure Responses:**

- `400 BadRequest` when username or password is missing.
- `401 Unauthorized` when the credentials are invalid.

**Example Request:**

```bash
curl -X POST http://localhost:3000/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"user1","password":"user123"}'
```

---

### 4.2 `GET /mail/:id`

**Description:**  
Retrieve a single mail message by ID.

**Authentication:**  
Requires `Authorization: Bearer <token>`.

**Access Rules:**

- `admin`: may view any mail ID.
- `user`: may view only mail where `mail.userId` matches their own `userId`.

**Example Request:**

```bash
curl http://localhost:3000/mail/2 \
  -H "Authorization: Bearer <token>"
```

**Example Success Response (200):**

```json
{
  "id": 2,
  "userId": 2,
  "subject": "Hello User1",
  "body": "Your report is ready."
}
```

**Example Forbidden Response (403):**

```json
{
  "error": "Forbidden",
  "message": "User does not have permission to access this resource.",
  "statusCode": 403,
  "requestId": "e7dd9ca0-8bf6-4d18-bc4e-4fbeefef1f8c",
  "timestamp": "2026-04-13T17:00:00.000Z"
}
```

**Example Unauthorized Response (401):**

```json
{
  "error": "Unauthorized",
  "message": "Missing or invalid Authorization header.",
  "statusCode": 401,
  "requestId": "c8ec0b05-b6bc-4828-9c64-6d83092f9b7c",
  "timestamp": "2026-04-13T17:00:00.000Z"
}
```

---

### 4.3 `GET /status`

**Description:**  
Simple health-check endpoint.

**Authentication:**  
No authentication required.

**Example Response (200):**

```json
{
  "status": "ok"
}
```

---

## 5. Rate Limiting

Rate limiting is implemented with a simple in-memory fixed window.

- Uses environment variables:
  - `RATE_LIMIT_MAX`
  - `RATE_LIMIT_WINDOW_SECONDS`
- Keyed by:
  - `req.user.userId` for authenticated requests
  - otherwise `req.ip`
- Applied to `GET /mail/:id`

When the limit is exceeded, the API returns `429 TooManyRequests` and includes a `Retry-After` header. The JSON body also includes `retryAfter`.

**Example Rate-Limited Response (429):**

```json
{
  "error": "TooManyRequests",
  "message": "Rate limit exceeded. Please try again later.",
  "statusCode": 429,
  "requestId": "2ca6e4dc-6d89-4a44-af4d-589d38a83d70",
  "timestamp": "2026-04-13T17:00:00.000Z",
  "retryAfter": 42
}
```

---

## 6. Error Response Format

All errors go through the centralized error handler and return this structure:

```json
{
  "error": "SomeCategory",
  "message": "Safe explanation",
  "statusCode": 400,
  "requestId": "...",
  "timestamp": "2026-04-13T17:00:00.000Z"
}
```

Common categories used by this API:

- `BadRequest`
- `Unauthorized`
- `Forbidden`
- `NotFound`
- `TooManyRequests`
- `InternalServerError`

---

## 7. Example Flows

### 7.1 Happy Path: Login + Access Own Mail

1. Authenticate as `user1`:

```bash
curl -X POST http://localhost:3000/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"user1","password":"user123"}'
```

Example response:

```json
{
  "token": "<jwt-token>"
}
```

2. Use the token to access mail `2`, which belongs to `user1`:

```bash
curl http://localhost:3000/mail/2 \
  -H "Authorization: Bearer <jwt-token>"
```

Example response:

```json
{
  "id": 2,
  "userId": 2,
  "subject": "Hello User1",
  "body": "Your report is ready."
}
```

### 7.2 Error Path: User Accessing Someone Else’s Mail

1. Authenticate as `user1`.
2. Attempt to access mail `1`, which belongs to another user.

```bash
curl http://localhost:3000/mail/1 \
  -H "Authorization: Bearer <jwt-token>"
```

Example response:

```json
{
  "error": "Forbidden",
  "message": "User does not have permission to access this resource.",
  "statusCode": 403,
  "requestId": "b25cb5e4-61c7-4587-b84e-0424cfdf2d17",
  "timestamp": "2026-04-13T17:00:00.000Z"
}
```

### 7.3 Error Path: Rate-Limited Request

After exceeding the configured number of requests in the current time window:

```bash
curl http://localhost:3000/mail/2 \
  -H "Authorization: Bearer <jwt-token>"
```

Example response:

```json
{
  "error": "TooManyRequests",
  "message": "Rate limit exceeded. Please try again later.",
  "statusCode": 429,
  "requestId": "f629dd4a-db2c-49de-ac35-f8d52a10cc4b",
  "timestamp": "2026-04-13T17:00:00.000Z",
  "retryAfter": 30
}
```
