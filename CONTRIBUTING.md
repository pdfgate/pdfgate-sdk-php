# Contributing

We welcome bug reports, feature requests, and pull requests.

For non-trivial changes, please open or link to an issue before submitting a pull request so scope and approach are aligned early. Small typo or documentation fixes can be submitted directly.

## Compatibility with supported PHP versions

This SDK supports the PHP versions listed in [README.md](README.md#requirements). Contributions must remain compatible with all supported versions.

## Development setup and release process

Please use the [Development](README.md#development) section in `README.md` for local setup, test commands, and release/tagging instructions.

## Documentation and tests for public API changes

If a pull request changes public SDK behavior (new method, changed payload, changed exception behavior, or modified response handling), it must include:

- Inline PHPDoc updates in `src/` for affected public APIs (`@param`, `@return`, `@throws` as needed).
- Curated documentation updates in `README.md` and/or `docs/` guides/reference pages.
- Tests that cover the changed behavior.

Public API changes are considered incomplete until code, tests, and documentation are all updated together.
