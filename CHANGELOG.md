# Yii DB Migration Change Log

## 2.0.0 under development

- Enh #274: Refactor for compatibility with `yiisoft/db` package (@Tigrov)
- Bug #277: Fix when there is a namespace but the directory does not exist (@Tigrov)
- Chg #279: Use `ColumnBuilder` class to create table column definitions (@Tigrov)
- Enh #282, #283: Adapt to Yii DB changes (@Tigrov)
- Bug #286: Explicitly mark nullable parameters (@vjik)

## 1.2.0 November 27, 2024

- Enh #268: Don't use Yii DB deprecated methods in `Migrator` (@BaBL86, @vjik)
- Enh #272: Raise minimum PHP version to `^8.1` with minor refactoring (@Tigrov)

## 1.1.0 December 24, 2023

- New #250: Add shortcuts for UUID columns (@viktorprogger)

## 1.0.0 December 21, 2023

- Initial release.
