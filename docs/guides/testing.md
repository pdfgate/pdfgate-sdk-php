# Testing and Tooling

## Install dependencies

```bash
composer install
```

## Unit tests

```bash
composer run test:unit
```

## Acceptance tests (real API)

```bash
PDFGATE_API_KEY=your_key composer run test:acceptance
```

## Static analysis

```bash
composer run stan
```

## Build documentation

Generate API reference from PHPDoc:

```bash
composer run docs:api
```

If phpDocumentor is not globally available, set `PHPDOC_BIN`:

```bash
PHPDOC_BIN="php /absolute/path/phpDocumentor.phar" composer run docs:api
```

Validate markdown links in `README.md` and `docs/`:

```bash
composer run docs:check-links
```

Build docs and run link checks:

```bash
composer run docs:build
```
