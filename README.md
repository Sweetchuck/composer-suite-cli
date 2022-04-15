# Composer Suite CLI

[![CircleCI](https://circleci.com/gh/Sweetchuck/composer-suite-cli/tree/1.x.svg?style=svg)](https://circleci.com/gh/Sweetchuck/composer-suite-cli/?branch=1.x)
[![codecov](https://codecov.io/gh/Sweetchuck/composer-suite-cli/branch/1.x/graph/badge.svg?token=HSF16OGPyr)](https://app.codecov.io/gh/Sweetchuck/composer-suite-cli/branch/1.x)

Note that the [Composer Suite] and [Composer Suite CLI] projects are different
projects. \
[Composer Suite] is a Composer plugin. \
[Composer Suite CLI] is an individual CLI tool. \
Both projects have the same goal, but the method they work is different.

The easiest way to work with the [Composer Suite] Composer plugin is that,
when the plugin is already added to the requirements (`require-dev` or
`require`). \
If that is not the case, then the workarounds aren't obvious and
intuitive.

For example you are working on a third-party project to fix bugs or add new
features, and you need to overwrite something (e.g.: minimum PHP version) in the
composer.json, or add extra developer helper packages, but those modifications
aren't part of the scope, then this „Composer Suite CLI” tool comes into play.

The „Composer Suite CLI” reads the configuration from the same sources as the
„Composer Suite” plugin does it, but this is an individual CLI tool.

---

[Composer Suite]: https://github.com/Sweetchuck/composer-suite
[Composer Suite CLI]: https://github.com/Sweetchuck/composer-suite
