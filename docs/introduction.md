---
title: Introduction
weight: 1
---

## Introduction
@zeus Translatable is Filament support for Spatie's Laravel Translatable package.

## Features

- 🔥 Using Spatie translatable package
- 🔥 Default translatable locales & custom locale labels
- 🔥 Locale Switcher with optional session persistence
- 🔥 Support for create, edit, list, view, and manage pages
- 🔥 Optional validation across all configured locales (`validateAllLocales()`)
- 🔥 Optional fallback locale support (`useFallbackLocale()`)
- 🔥 Optional translatable field icons (`showIcon()`)
- 🔥 Setting the translatable locales for a particular resource
- 🔥 Translating relationships in forms and relation managers

## More Details

**✨ to learn more about Translatable, please visit:**

- [Docs](https://larazeus.com/docs/spatie-translatable)

## Important Note on Using the Locale Switcher

Please be aware that there are some known limitations when using the locale switcher with certain complex field types.

To avoid potential issues, we recommend disabling the locale switcher and using the specified field types instead.

### Available Components for Translatable Fields:

* https://filamentphp.com/plugins/solution-forest-translate-field
* https://filamentphp.com/plugins/mvenghaus-translatable-inline
* https://filamentphp.com/plugins/outerweb-translatable-fields
* https://filamentphp.com/plugins/34ml-translatable-field

You can also create your own custom fields. Please refer to the following example:

* https://github.com/lara-zeus/chaos/blob/1.x/src/Forms/Components/MultiLang.php

