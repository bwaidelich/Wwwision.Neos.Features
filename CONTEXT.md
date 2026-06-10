# Wwwision.Neos.Features

A Neos backend module for toggling and configuring optional pieces of behaviour ("features"). Features are declared in Settings, grouped for presentation, and may depend on one another. Each feature has a lifecycle (activate / update options / deactivate) backed by a PHP implementation.

## Language

**Feature**:
A named, toggleable unit of behaviour declared in Settings. Has an id, name, description, optional group, and optional dependencies on other features.
_Avoid_: Flag, toggle, module

**Feature implementation**:
The PHP object that runs a feature's lifecycle side effects. Comes in two kinds (see below). Bound to a feature via the `objectName` setting.

**Configurable feature**:
A feature whose implementation declares typed **options** and whose lifecycle methods receive them: `activate(options)`, `updateOptions(previous, new)`, `deactivate(previous)`.
_Avoid_: feature with options

**Optionless feature**:
A feature whose implementation takes no options. Its interface carries only `activate()` and `deactivate()` — no `optionsClassName`, no `updateOptions` (there is nothing to update). The default `NoopFeature` (a feature declared with no `objectName`) is the canonical optionless implementation.
_Avoid_: feature without options, no-options feature, empty-options feature

**Options**:
A feature's configuration: a typed, readonly shape whose schema drives the activation/update form. Only configurable features have options.
_Avoid_: config, settings (reserved for Neos/Flow Settings.yaml)

**Noop feature**:
The built-in optionless implementation used when a feature declares no `objectName`. Does nothing on activate/deactivate.

## Flagged ambiguities

- **Optionless vs noop**: orthogonal historically, but in this design every optionless feature with no custom behaviour *is* the `NoopFeature`. A custom implementation may still be optionless (real side effects, no config).
