# Customer Profiles

Customer profiles contain versioned configuration only. They are allowed to select and configure public IT-Kayali Commerce Suite capabilities, but they must not patch or fork the generic product core.

A profile may contain approved values such as:

- branding and logos;
- color and typography tokens;
- local font assignments;
- contact information;
- language configuration;
- responsive layout assignments;
- header/footer/menu selections;
- enabled module configuration;
- document-template configuration.

Do not commit passwords, API keys, private certificates, production database dumps, order/customer data or other secrets here.

The first reference implementation is stored under `al-lord/`. Its configuration must remain isolated from the generic packages in `packages/`.
