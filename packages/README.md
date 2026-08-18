# Packages

The Commerce Suite is delivered as independently versioned WordPress packages.

| Package | Type | Current status | Responsibility |
| --- | --- | --- | --- |
| `itk-commerce-theme` | Theme | Development | Design system, reusable layouts, commerce UI and responsive presentation contracts |
| `itk-commerce-core` | Plugin | Development | Settings, modules, profiles, import/export foundation, updates and roles |
| `itk-commerce-layouts` | Module | Development | Header/Footer/Mega-menu builders, commerce page models and profile-driven presentation |
| `itk-commerce-search-filter` | Module | Development | Filter schema, shareable URL state, WooCommerce query integration, AJAX search/filter and off-canvas filters |
| `itk-commerce-multilingual` | Module | Development | Languages, translations, RTL/LTR, hreflang, WooCommerce/order language, transfer and review workflow |
| `itk-commerce-elementor` | Module | Development | Optional Elementor category/widgets and Commerce extension-area integration |
| `itk-commerce-documents` | Module | Development | Invoice, delivery note, return form and packing list with HTML/PDF renderer contract |
| `itk-commerce-badges` | Module | Development | Sale percentage and custom product badges through the Theme badge contract |
| `itk-commerce-wishlist-compare` | Module | Development | Browser wishlist and bounded comparison using WooCommerce Store API reads |
| `itk-commerce-gift-boxes` | Module | Development | Category-bounded configurable gift-box selections persisted into cart/order lines |
| `itk-commerce-code-manager` | Module | Development | Controlled HTML/CSS/JS and explicit opt-in PHP extension points |

All business features remain separate from the Theme even where the Theme exposes presentation slots. Reusable packages must remain customer-neutral; customer branding/configuration belongs below `client-profiles/`.

`Development` means the implementation exists and is covered by repository validation, but customer-specific production compatibility is not declared until the Phase 0 environment audit and the Phase 8/9 staging gates are completed against real WordPress/WooCommerce environments.
