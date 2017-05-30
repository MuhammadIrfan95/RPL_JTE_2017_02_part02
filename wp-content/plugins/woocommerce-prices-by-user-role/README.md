# Codex

## Update prices for all roles

```php
$prices = array(
    'group1' => 100
);

update_prices_by_roles($product_id, $prices);
```

## Get all prices for product

```php
get_product_prices($product_id);
```

## Get user price for product

```php
get_price_by_user_id($product_id, $user_id);
```