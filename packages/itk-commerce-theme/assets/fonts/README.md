# Local fonts

Font files for a customer installation belong here only when IT-Kayali/the customer has the required usage rights.

Rules:

- prefer WOFF2;
- do not load Google Fonts or other remote font CDNs by default;
- customer-specific font files should normally be supplied by the customer profile/build process rather than hard-coded into the generic theme;
- keep Arabic and Latin font assignments configurable;
- preload only fonts actually required above the fold.

The Commerce Core/font module will later generate the final `@font-face` declarations from validated local files.
