# BlueFairy_OrderCommentsHyva

Hyvä theme companion for BlueFairy Order Comments (`bluefairy/module-order-comments`). It renders the order-comment fields, a dropdown and a free-text comment, on Hyvä storefronts with Alpine.js: on the cart page, and at checkout under Hyvä Checkout.

This package is free and open source under the MIT licence. **The Order Comments base module it extends is a paid, separately licensed product.** This package does nothing without it.

---

## Requirements

- `bluefairy/module-order-comments` **1.2.1 or later** (`^1.2.1` in `composer.json`), the paid base module, installed and licensed.
- Hyvä Theme: `hyva-themes/magento2-theme-module` `^1.5`.
- Hyvä Checkout (`hyva-themes/magento2-hyva-checkout`) for the checkout fields. The checkout layout adds the fields to the block `hyva.checkout.payment.method.list`, which only Hyvä Checkout provides; without it Magento ignores the reference and no fields appear at checkout. The cart page needs only the Hyvä theme module.
- Magento 2.4.8 or 2.4.9 (`magento/framework` `~103.0.8`).
- PHP `~8.3.0 || ~8.4.0 || ~8.5.0`, within the PHP versions your Magento release supports.

---

## Installation

Install the base module first, from wherever you obtained it. Then install this package from packagist.org:

```bash
composer require bluefairy/module-order-comments-hyva
php bin/magento module:enable BlueFairy_OrderCommentsHyva
php bin/magento setup:upgrade
php -d memory_limit=2G bin/magento setup:di:compile
php bin/magento cache:flush
```

Then rebuild the Hyvä theme's CSS (`npm run build` in the theme's `web/tailwind` directory), so the utility classes this module's templates use are in the compiled stylesheet.

---

## What It Does

- **Cart page:** adds the comment panel (`bluefairy.ordercomments.cart`) to the `checkout.cart.form.before` container, when Order Comments is enabled and **Show on Cart Page** is on. The comment is saved to the cart through the base module's `bluefairy_ordercomments/cart/saveComment` endpoint.
- **Checkout (Hyvä Checkout):** adds the comment fields (`bluefairy.ordercomments.hyva.checkout`) to the payment step, inside `hyva.checkout.payment.method.list`.
- Both forms show the value the cart already holds, mark fields `required` when the base module's configuration says so, and limit the text to the configured character limit.
- An observer on Hyvä's config generator registers this module's `view/frontend/tailwind/module.css`, whose `@source` covers this module's templates, so the theme build includes their classes.

---

## Configuration

This module has no configuration of its own. Every setting comes from the base module, under **Stores → Configuration → Sales → Order Comments**.

---

## Licence

MIT, from release 1.2.2. See [`LICENSE.txt`](LICENSE.txt). Copyright (c) 2026 Blue Fairy Development LLC. Releases up to 1.2.1 were distributed under the Order Comments end-user licence agreement.

The MIT licence covers this package only. `bluefairy/module-order-comments` stays under its own licence. Hyvä Theme and Hyvä Checkout are licensed separately by Hyvä.
