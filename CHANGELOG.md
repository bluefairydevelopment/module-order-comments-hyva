# Changelog — BlueFairy_OrderCommentsHyva

## 1.2.2 — 2026-10-28

### Changed
- **This package is now free and open source, under the MIT licence.** `LICENSE.txt` is the MIT licence and `composer.json` declares `MIT`. Nothing in the code changes. The core package it extends, `bluefairy/module-order-comments`, keeps its own licence.
- It is now published from its own repository and installs from packagist.org: `composer require bluefairy/module-order-comments-hyva`.
- Adds a README: what the package does, its requirements (the Order Comments base module `^1.2.1` and the Hyvä theme), installation and licence.
- Every file's copyright notice now points to the MIT licence instead of reserving all rights.

## 1.2.1 — 2026-10-15

Follows the core package, `BlueFairy_OrderComments` 1.2.1. The Hyvä fields carry the `required` attribute, and the core package also enforces it on the server.

### Changed
- Requires `bluefairy/module-order-comments` `^1.2.1` (was `^1.2.0`), in step with the GraphQL package. This package calls nothing new in the core.
- Wording and comment clean-up.

## 1.2.0 — 2026-10-14

### Changed
- **Installs on Magento 2.4.8 and on PHP 8.3**, with the core package: `magento/framework` is `~103.0.8` (was `^103.0.9`) and `php` is `~8.3.0 || ~8.4.0 || ~8.5.0`.
- Requires `bluefairy/module-order-comments` `^1.2.0` (was `^1.1.0`), in step with the GraphQL package. This package calls nothing new in the core.
- Licence: the independent-contractor clause is reworded; the meaning is unchanged.

## 1.1.2 — 2026-09-25

### Fixed
- **The cart-page comment panel had no background or border colour in any Hyvä theme that does not load Hyvä's deprecated fallback tokens.** Its outer element used `bg-container-lighter` and `border-container-lighter`. Hyvä's default theme defines `container-*` only in `web/tailwind/utilities/fallback.css`, marked deprecated since Hyvä 1.3, and none of its own templates use them; a child theme that does not import that file has no rule for either class, so the panel rendered unstyled with no error anywhere. The panel now uses core Tailwind utilities that every build defines: `bg-white border border-gray-300`, the same stroke Hyvä's cart uses for its item dividers. A unit test now rejects any colour utility outside Tailwind's own palette, `container-*` by name

## 1.1.1 — 2026-09-25

### Fixed
- **The package now ships a licence, and it is the one composer.json declares.** `composer.json` has always said `proprietary`, and until 1.1.1 `bluefairy/module-order-comments-hyva` shipped no licence file at all — no grant, no restriction, no limitation of liability. `LICENSE.txt` is now the Blue Fairy Development LLC End User License Agreement, its Software definition names all three Order Comments packages. A template for attorney review, not legal advice

## 1.1.0 — 2026-09-10

### Added
- `composer.json`: a `suggest` for `hyva-themes/magento2-hyva-checkout`, naming it as required for the CHECKOUT comment fields specifically. That package is what defines `hyva.checkout.payment.method.list`, the block this module's checkout layout targets; without it Magento silently ignores the reference and no fields appear at checkout, with no error anywhere. The cart-page integration needs only `hyva-themes/magento2-theme-module`. Declaring it makes an optional dependency visible instead of leaving half the package inert for reasons a merchant cannot see

### Fixed
- `view/frontend/layout/checkout_cart_index.xml` and `checkout_index_index.xml` are renamed to **`hyva_checkout_cart_index.xml`** and **`hyva_checkout_index_index.xml`**. A layout file under `view/frontend/layout/` merges into **every** frontend theme, not only Hyvä ones, and `checkout.cart.form.before` exists in the core Luma cart layout as well as the Hyvä one — so on a store serving a second, non-Hyvä store view this package rendered an Alpine template into a theme with no Alpine to interpret `x-data`: a comment box that looks present and does nothing, with no error anywhere. `Hyva\Theme\Observer\AddLayoutHandles` adds the `hyva_`-prefixed handle only when a Hyvä theme is active, so the prefix is what makes the file unreachable from a non-Hyvä theme; it is the usual way to scope a Hyvä layout file. The obvious alternative — passing `Hyva\Theme\Service\CurrentTheme` as a layout argument — is not available and must not be reintroduced: layout arguments are constructed during layout generation and resolving the design theme there re-enters it and 500s the page
- `view/frontend/templates/cart/order-comment.phtml`: gate on the master **Enable Order Comments** switch as well as Show on Cart Page. `depends` in `system.xml` hides the child field in the admin form without clearing its stored row, so a store that disabled the module kept rendering the cart fields while every order write path refused to carry what was typed into them. The checkout template in this same package had always gated correctly, so the two disagreed
- `OrderCommentsHyva` templates: the new `comment_values` layout argument is read null-safely. The templates gained a **second** required argument, and a merchant layout override that redeclares the block without it would have fatalled the cart page on `getDropdown() on null`. Our own layout always supplies it — there is a test asserting that — so the fallback covers third-party overrides only, where an unseeded field is a smaller failure than a dead page
- `view/frontend/templates/cart/order-comment.phtml`, `checkout/order-comment.phtml` and both layout files: the Alpine state is seeded from the quote via a new `comment_values` layout argument instead of from a literal `''`. A field that renders empty while a value is stored erases that value, because an empty field means "cleared" to the server. Within the cart page it also meant that returning to the cart and editing the text wiped the dropdown the shopper had chosen — and the empty field never showed them what they were about to lose
- `view/frontend/layout/checkout_cart_index.xml`: the cart-page comment fields now render. The block was attached to `checkout.cart.form`, whose template emits only three named children and silently discards anything else, so the advertised cart capture had never appeared on any theme. Attached to the `checkout.cart.form.before` container instead
- `Observer/RegisterModuleForHyvaConfig`, `etc/frontend/events.xml`, `view/frontend/tailwind/module.css`: register the module with Hyvä's Tailwind build. `npx hyva-sources` only discovers modules listed in `app/etc/hyva-themes.json`, and a module reaches that list by observing `hyva_config_generate_before` — without it, Tailwind never scanned this module's templates and every utility class used only here was purged from the compiled stylesheet

### Changed
- `composer.json`: `bluefairy/module-order-comments` raised from `^1.0.3` to `^1.0.4`. Both layout files pass `BlueFairy\OrderComments\Model\Quote\CurrentComment` as an `xsi:type="object"` argument, and that class does not exist in the published 1.0.3 — which `^1.0.3` admitted. Composer resolves the pair without complaint and the store then fails on a class that is not there. The GraphQL companion had the same defect and was corrected in the same release. The three packages are published from a single tag, but a merchant does not always receive a matched set: each resolves independently
- `composer.json`: `php` constraint corrected from `^8.3` to `~8.4.0 || ~8.5.0`; `bluefairy/module-order-comments` and `hyva-themes/magento2-theme-module` given real version ranges instead of `*`; added the previously undeclared `magento/framework`

## 1.0.3 — 2026-08-12

### Changed
- No changes in this module.

## 1.0.2 — 2026-08-12

### Fixed
- `view/frontend/templates/cart/order-comment.phtml`: replace `document.querySelector('input[name="form_key"]')` with `hyva.getFormKey()` — the previous approach could return an empty string if the input was not yet rendered, causing CSRF token to be missing from the save request
- `view/frontend/templates/cart/order-comment.phtml`: add error state (`error: false`) and display error message on failed save; add `$cleanup` in `init()` to cancel any pending debounce timer when the component is destroyed; add `aria-live="polite"` to status region
- `view/frontend/templates/cart/order-comment.phtml`: fix `:maxlength="false"` to `:maxlength="null"` — Alpine.js removes the attribute when bound to `null`; `false` is coerced to string `"false"` in some browsers
- `view/frontend/templates/checkout/order-comment.phtml`: remove redundant `x-init="init()"` — Alpine.js automatically calls `init()` when defined in the component data, causing double-invocation
- `view/frontend/templates/checkout/order-comment.phtml`: add `$cleanup` handler in `init()` to remove the `checkout:payment:before-submit` event listener when the component is destroyed; prevents listener accumulation on SPA navigations
- `view/frontend/templates/checkout/order-comment.phtml`: fix static label `for` attribute — was dynamically bound (`:for`) with a literal string, which is equivalent to a static attribute
- `view/frontend/templates/checkout/order-comment.phtml`: fix `:maxlength="false"` to `:maxlength="null"`; simplify `dropdownLabel` JS expression
- `etc/module.xml`: add `Hyva_Theme` to module sequence so this module always loads after the Hyva theme is registered
- `composer.json`: declare `hyva-themes/magento2-theme-module` as an explicit Composer dependency

## 1.0.0 — 2026-08-11

### Added
- Hyvä Checkout companion — renders dropdown and free-text comment fields within the Hyvä checkout flow
- Cart and checkout layout overrides and templates for comment field display
