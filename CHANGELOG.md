# Yii DB Migration Change Log

## 2.0.1 under development

- no changes in this release.

## 2.0.0 December 09, 2025

- New #295: Add `--force-yes` (`-y`) option to `migrate:create`, `migrate:up`, `migrate:down` and `migrate:redo`
  commands to skip confirmation prompts (@vjik)
- Chg #279: Use `ColumnBuilder` class to create table column definitions (@Tigrov)
- Chg #287, #308: Change supported PHP versions to `8.1 - 8.5` (@Tigrov, @vjik)
- Chg #290: Remove `ext-filter` from `require` section of `composer.json` (@Tigrov)
- Chg #300: Replace deprecated `self::getDefaultName()` with `$this->getName()` (@Tigrov)
- Chg #311: Remove `AbstractMigrationBuilder` (@vjik)
- Enh #274, #297: Refactor for compatibility with `yiisoft/db` package (@Tigrov)
- Enh #282, #283, #293: Adapt to Yii DB changes (@Tigrov)
- Enh #287: Minor refactoring (@Tigrov)
- Enh #289: Revert transactional migration when adding migration to history fails (@Tigrov)
- Enh #292: Improve base migration template (@vjik)
- Enh #299: Update `MigrationBuilder::update()` method to adapt changes in `yiisoft/db` (@rustamwin)
- Enh #301: Add `MigrationBuilder::columnBuilder()` method (@Tigrov)
- Enh #311: Explicitly mark readonly properties (@vjik)
- Bug #277: Fix when there is a namespace but the directory does not exist (@Tigrov)
- Bug #286: Explicitly mark nullable parameters (@vjik)

## 1.2.0 November 27, 2024

- Enh #268: Don't use Yii DB deprecated methods in `Migrator` (@BaBL86, @vjik)
- Enh #272: Raise minimum PHP version to `^8.1` with minor refactoring (@Tigrov)

## 1.1.0 December 24, 2023

- New #250: Add shortcuts for UUID columns (@viktorprogger)

## 1.0.0 December 21, 2023

- Initial release.
