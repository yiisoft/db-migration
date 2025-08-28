# Yii DB Migration Change Log

## 2.0.0 under development

- Enh #274, #297: Refactor for compatibility with `yiisoft/db` package (@Tigrov)
- Bug #277: Fix when there is a namespace but the directory does not exist (@Tigrov)
- Chg #279: Use `ColumnBuilder` class to create table column definitions (@Tigrov)
- Enh #282, #283, #293: Adapt to Yii DB changes (@Tigrov)
- Bug #286: Explicitly mark nullable parameters (@vjik)
- Chg #287: Change supported PHP versions to `8.1 - 8.4` (@Tigrov)
- Enh #287: Minor refactoring (@Tigrov)
- Enh #289: Revert transactional migration when adding migration to history fails (@Tigrov)
- Chg #290: Remove `ext-filter` from `require` section of `composer.json` (@Tigrov)
- Enh #292: Improve base migration template (@vjik)
- New #295: Add `--force-yes` (`-y`) option to `migrate:create`, `migrate:up`, `migrate:down` and `migrate:redo`
  commands to skip confirmation prompts (@vjik)
- Chg #300: Replace deprecated `self::getDefaultName()` with `$this->getName()` (@Tigrov)

## 1.2.0 November 27, 2024

- Enh #268: Don't use Yii DB deprecated methods in `Migrator` (@BaBL86, @vjik)
- Enh #272: Raise minimum PHP version to `^8.1` with minor refactoring (@Tigrov)

## 1.1.0 December 24, 2023

- New #250: Add shortcuts for UUID columns (@viktorprogger)

## 1.0.0 December 21, 2023

- Initial release.
