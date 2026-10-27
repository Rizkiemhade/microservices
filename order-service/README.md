<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Order API contract

### Create an order

`POST /api/orders` accepts a user ID and an optional idempotency key. The Order
Service retrieves the user's cart from Cart Service, then verifies each product
with Product Service. Clients must not send cart items, prices, or totals.

```json
{
  "user_id": 42,
  "idempotency_key": "checkout-42-001"
}
```

On success, the API responds with HTTP `201` and an order representation. A
retry using the same user and idempotency key returns the existing order with
HTTP `200`.

```json
{
  "data": {
    "id": 101,
    "user_id": 42,
    "status": "pending",
    "total_amount": "50000.00",
    "currency": "IDR",
    "items": [
      {
        "product_id": 7,
        "product_name": "Example product",
        "unit_price": "25000.00",
        "quantity": 2
      }
    ]
  }
}
```

Invalid input or an empty cart responds with HTTP `422` and Laravel's standard
validation body:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "cart": [
      "Cannot create an order from an empty cart."
    ]
  }
}
```

A missing product responds with HTTP `422`. If Cart or Product Service is
unavailable, the endpoint responds with HTTP `503` and does not create an order.
The order and its product snapshots are persisted together in a database
transaction.

### Read orders

Get one order belonging to a user:

```text
GET /api/users/{userId}/orders/{orderId}
```

List a user's orders (15 per page):

```text
GET /api/users/{userId}/orders
```

Both endpoints include the order status and its item snapshots. The detail
endpoint returns `404` when the order does not exist or does not belong to the
requested user. The list response uses Laravel pagination fields (`data`,
`links`, and `meta`).

### Service URLs

The Order Service reads the Cart and Product Service base URLs from
`CART_SERVICE_URL` and `PRODUCT_SERVICE_URL`. Local defaults are
`http://localhost:3001` and `http://localhost:3000`. Each client uses its own
`*_TIMEOUT` and `*_CONNECT_TIMEOUT` settings, in seconds.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
