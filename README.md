# Laravel Repository Pattern Generator
This 'Package' support only api
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

## 🧩 Dependency Injection Binding

The package automatically registers repository bindings in the service container:

```php
$this->app->when(ProductService::class)
    ->needs(RepositoryInterface::class)
    ->give(ProductRepository::class);
```
## if you not install api this return in your terminal
out when installed api then try this command again
```bash
php artisan make:repo student --all
Created migration file for students table.
Cannot write to C:\xampp_new\htdocs\first_package\routes/api.php. Please manually add the following to routes/api.php:
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentController;

Route::resource('students', StudentController::class);
Repository pattern files for Student created successfully!
```

### How it works:
1. **when()**: Specifies the service class that needs dependency
2. **needs()**: Defines the interface/abstract type needed
3. **give()**: Provides the concrete implementation

You can find these bindings in:
`app/Providers/RepositoriesServiceProvider.php`
`bootstrap/providers/RepositoriesServiceProvider.php`

