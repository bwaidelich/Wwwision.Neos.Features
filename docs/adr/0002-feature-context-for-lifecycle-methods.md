# Introduce a FeatureContext parameter on lifecycle methods, backed by two global config files

## Status

accepted

## Context

Feature implementations that need to write to a YAML configuration file (e.g. `ActivateNodeTypeFeature` writing NodeTypes overrides) had to obtain a `YamlConfigurationFile` themselves — either hardcoding a path or, like `ActivateNodeTypeFeatureFactory`, exposing `filePath`/`fileName` factory options and a `$defaultConfigurationPath` constructor argument overridable in `Objects.yaml`. Every implementation that touches a config file repeats this wiring.

## Decision

`ConfigurableFeatureImplementation` and `OptionlessFeatureImplementation` lifecycle methods (`activate`, `updateOptions`, `deactivate`) gain a `FeatureContext` as their first parameter. `FeatureContext` exposes exactly two accessors, `settingsFile()` and `nodeTypesFile()`, each returning a `YamlConfigurationFile` for one shared file. The two file paths are controlled by global settings (`Wwwision.Neos.Features.configurationFiles.settings.path` / `.nodeTypes.path`), defaulting to `<FLOW_PATH_CONFIGURATION>/Settings.Features.yaml` and `<FLOW_PATH_CONFIGURATION>/NodeTypes.Features.yaml`.

As a consequence, `ActivateNodeTypeFeatureFactory` drops its `filePath`/`fileName` options and `$defaultConfigurationPath` constructor argument; `ActivateNodeTypeFeature` drops its `YamlConfigurationFile` constructor parameter and calls `$context->nodeTypesFile()` inside `activate()`/`deactivate()` instead. All features now share the same NodeTypes file — a feature can no longer point at a custom one.

## Considered alternatives

- **Deliver the context via constructor injection instead of the method signature.** Rejected: would have avoided the breaking change flagged as a concern in ADR-0001, but every implementation would still need its own factory (or an `Objects.yaml` override) just to receive it — the boilerplate this decision exists to remove.
- **Generic `configurationFile(string $fileName)` lookup instead of two fixed accessors.** Rejected for now in favour of the two concrete accessors that match today's two known use cases; revisit if a third shared file emerges.
- **Keep per-feature `filePath`/`fileName` override on `ActivateNodeTypeFeatureFactory`.** Rejected in favour of one global file for simplicity; a feature needing a genuinely separate file can still construct its own `YamlConfigurationFile` outside the context.

## Consequences

- Breaking change: every existing `FeatureImplementation` (including third-party ones) must add the `FeatureContext` parameter to its lifecycle methods.
- `NoopFeature` and any other implementation that doesn't need a config file simply ignores the parameter.
- A feature can no longer redirect `ActivateNodeTypeFeature` to a custom NodeTypes file; all features sharing that implementation write to the same global file.
