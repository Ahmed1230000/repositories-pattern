# Laravel Repository Pattern Generator

A powerful package to automatically generate the complete repository pattern structure for your Laravel applications.

## Features

- 🚀 Auto-generates all repository pattern components with one command
- 📦 Includes: Model, Repository, Service, Controller, Form Requests, Migration
- 🔄 Supports both API and web routes
- ⚡ Easily extendable base classes
- 🔧 Customizable stubs

## Installation

1. Require the package via Composer:

```bash
composer require ahmedmahmoud/repository-pattern
```
2.(Optional) Publish stubs for customization:
```bash
php artisan vendor:publish --tag=repository-stubs --force
```
3. Run the setup command to install base files:

```bash
php artisan repository:setup
```
4. Generate complete structure for a new model:

```bash
php artisan make:repo {name : The name of the repository} --all (Includes: Model, Repository, Service, Controller, Form Requests, Migration}
```

## Service Provider:

- $this->app->when(ProductService::class)
    ->needs(RepositoryInterface::class)
    ->give(ProductRepository::class);
