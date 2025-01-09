# GoogleTagManager Class Documentation

## Overview
The **GoogleTagManager** class provides a convenient way to generate Google Tag Manager events for various eCommerce actions, such as viewing an item, adding to cart, and completing a purchase. It dynamically generates the required JavaScript for triggering Google Tag Manager events.

---

## Public Methods

### `event($event, $data)`
Generates the JavaScript code for a specified Google Tag Manager event.

#### Parameters
- **`$event`** (string): The name of the event. Supported values include:
  - `view_item`
  - `add_to_cart`
  - `view_cart`
  - `begin_checkout`
  - `login`
  - `purchase`

- **`$data`** (array): An associative array of event data. Common keys include:
  - `name` (string): The name of the product.
  - `partNumber` (string): The product's part number.
  - `price` (float): The price of the product.
  - `brand` (string): The product brand.
  - `category` (string): The product category.
  - `variant` (string): The product variant, such as color or size.
  - `quantity` (int): The quantity of the product.

#### Returns
- (string): A string containing the generated JavaScript code for embedding in an HTML page.

#### Example Usage
```php
use True\GoogleTagManager;

$eventData = [
    'name' => 'Product Name',
    'partNumber' => '12345',
    'price' => 29.99,
    'brand' => 'Brand Name',
    'category' => 'Category Name',
    'variant' => 'Color: Red',
    'quantity' => 1
];

$GoogleTagManager = new GoogleTagManager();
echo $GoogleTagManager->event('view_item', $eventData);
```

#### Output
```html
<script>
    gtag("event", "view_item", {
        "name": "Product Name",
        "partNumber": "12345",
        "price": 29.99,
        "brand": "Brand Name",
        "category": "Category Name",
        "variant": "Color: Red",
        "quantity": 1
    });
</script>
```

---

## Supported Events

| Event Name      | Description                        |
|-----------------|------------------------------------|
| `view_item`     | Triggered when a user views a product. |
| `add_to_cart`   | Triggered when a user adds a product to the cart. |
| `view_cart`     | Triggered when a user views their shopping cart. |
| `begin_checkout`| Triggered when a user starts the checkout process. |
| `login`         | Triggered when a user logs in. |
| `purchase`      | Triggered when a user completes a purchase. |

---

## Summary
The **GoogleTagManager** class simplifies the process of generating Google Tag Manager events for common eCommerce actions. By using this class, you can easily integrate dynamic event tracking into your website, improving your analytics and marketing insights.

