# Laravel Test Generator

Auto generate the unit test file for the available routes

## Installation

The package can easily be installed by running `composer require vigneshc91/laravel-test-generator` in your project's root folder.

If you are running a version of Laravel < 5.5 also make sure you add `Vigneshc91\LaravelTestGenerator\TestGeneratorServiceProvider::class` to the `providers` array in `config/app.php`.

This will register the artisan command that will be available to you.


## Usage

Generating the test file is easy, simply run `php artisan laravel-test:generate` in your project root. This will write all the test cases into the file based on controller.

If you wish to filter for specific routes only, you can pass a filter attribute using --filter, for example `php artisan laravel-test:generate --filter='/api'`

If you wish to change the directory of creating the test file, you can pass a directory using --dir, for example `php artisan laravel-test:generate --dir='V1'`

If you wish to add the @depends attribute to all the function except the first function for running test cases synchronously, you can pass a sync attribute using --sync, for example `php artisan laravel-test:generate --sync='true'`
