# DbpRelayMakerBundle

[GitHub](https://github.com/digital-blueprint/relay-maker-bundle) |
[Packagist](https://packagist.org/packages/dbp/relay-maker-bundle)

[![Test](https://github.com/digital-blueprint/relay-maker-bundle/actions/workflows/test.yml/badge.svg)](https://github.com/digital-blueprint/relay-maker-bundle/actions/workflows/test.yml)

The maker bundle bundle provides Symfony commands for creating commonly required
code constructs. It's inspired by the [Symfony
MakerBundle](https://symfony.com/bundles/SymfonyMakerBundle/current/index.html)
but focuses on Relay specific boilerplate code.

## Creating a Bundle

Creates a new bundle in a `bundles` subdirectory and registers/installs it with the application.

```console
./bin/console dbp:relay:maker:make:bundle --help
Description:
  Create a new bundle

Usage:
  dbp:relay:maker:make:bundle [options]

Options:
      --vendor=VENDOR                  Vendor
      --category=CATEGORY              Category [default: "relay"]
      --unique-name=UNIQUE-NAME        Unique Name
      --friendly-name=FRIENDLY-NAME    Friendly Name
      --example-entity=EXAMPLE-ENTITY  Example Entity
      --dry-run                        Dry Run
      --no-confirm                     Bypass all confirmation questions, for automation

```
