# Split FeatureImplementation into configurable and optionless subtypes

## Status

accepted

## Context

A feature's behaviour is supplied by a `FeatureImplementation`. Originally this was a single generic interface `FeatureImplementation<TOptions>` whose lifecycle methods all received options (`activate(TOptions)`, `updateOptions(TOptions, TOptions)`, `deactivate(TOptions)`). Features that take no configuration were forced to fake this by declaring an empty options class (`EmptyFeatureOptions` / `NoopFeatureOptions`) and ignoring the argument, which leaked into the UI as empty activation/update forms and an empty "Optionen" row.

## Decision

Replace the single interface with a methodless marker interface `FeatureImplementation` and two subtypes:

- `ConfigurableFeatureImplementation<TOptions>` — declares `optionsClassName()` and the options-carrying lifecycle methods, including `updateOptions()`.
- `OptionlessFeatureImplementation` — only `activate()` and `deactivate()`; no options class and, deliberately, no `updateOptions()` because there is nothing to update.

`NoopFeature` (the implementation used when a feature declares no `objectName`) implements `OptionlessFeatureImplementation`. The marker base preserves the single `instanceof` check the settings adapter uses to validate the `objectName`.

## Considered alternatives

- **Keep the single generic interface + empty options class.** Rejected: forces optionless features to model and ignore fake options, and pushes the "no options" condition to a runtime empty-schema check rather than the type system.
- **Two `FeatureDefinition` subtypes mirroring the interfaces.** Rejected in favour of a single `FeatureDefinition` that carries a nullable `optionsClassName` (null ⇒ optionless), exposed via `hasOptions()`. Fewer classes; `FeatureSystem` branches on `hasOptions()` at the call site.

## Consequences

- `EmptyFeatureOptions` and `NoopFeatureOptions` are deleted; the `FeatureOptions` marker interface remains for configurable features.
- The optionless interface is a public extension point — changing its shape later is a breaking change for third-party implementations.
- The backend module distinguishes the two kinds via `Feature::hasOptions()`: optionless features activate via a one-click inline POST (no options form), never show an "update options" affordance, and omit the "Optionen" row. Deactivation is unchanged (a confirmation page) for all features.
