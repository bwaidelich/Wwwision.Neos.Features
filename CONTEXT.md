# Wwwision.Neos.Features

A Neos backend module for toggling and configuring optional pieces of behaviour ("features"). Features are declared in Settings, grouped for presentation, and may depend on one another. Each feature has a lifecycle (activate / update options / deactivate) backed by a PHP implementation.

## Language

**Feature**:
A named, toggleable unit of behaviour declared in Settings. Has an id, name, description, optional group, and optional dependencies on other features.
_Avoid_: Flag, toggle, module

**Feature implementation**:
The PHP object that runs a feature's lifecycle side effects. Comes in two kinds (see below). Provided to a feature either directly via the `objectName` setting or produced by a **feature factory**.

**Feature factory**:
An object that builds a **feature implementation** from **factory options**, letting one implementation be reused across features with different parameters. Declared via the `factoryClassName` setting (an alternative to `objectName`).

**Factory options**:
Static parameters that parameterise how a feature implementation is constructed: the raw `options` array declared in `Settings.yaml` under a factory. Build-time and invisible to the editor — distinct from the activation-time editor **Options** below. A factory may parse them into a value object itself.
_Avoid_: using the bare word "options" for these; always qualify as "factory options".

**Configurable feature**:
A feature whose implementation declares typed **options** and whose lifecycle methods receive them: `activate(options)`, `updateOptions(previous, new)`, `deactivate(previous)`.
_Avoid_: feature with options

**Optionless feature**:
A feature whose implementation takes no options. Its interface carries only `activate()` and `deactivate()` — no `optionsClassName`, no `updateOptions` (there is nothing to update). The default `NoopFeature` (a feature declared with no `objectName`) is the canonical optionless implementation.
_Avoid_: feature without options, no-options feature, empty-options feature

**Options**:
A feature's activation-time configuration: a typed, readonly shape whose schema drives the activation/update form, collected per `FeatureState`. Only configurable features have options. Distinct from build-time **factory options**.
_Avoid_: config, settings (reserved for Neos/Flow Settings.yaml)

**Noop feature**:
The built-in optionless implementation used when a feature declares no `objectName`. Does nothing on activate/deactivate.

## Flagged ambiguities

- **Optionless vs noop**: orthogonal historically, but in this design every optionless feature with no custom behaviour *is* the `NoopFeature`. A custom implementation may still be optionless (real side effects, no config).
- **Factory options vs (feature) Options**: two different things that can coexist on one feature. **Factory options** are static, build-time, declared in `Settings.yaml`, invisible to the editor. **Options** are activation-time, collected from the admin via the form, stored per state.
