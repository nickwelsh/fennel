<?php

use Illuminate\Http\Request;
use nickwelsh\Fennel\Enums\ImageFormat;
use nickwelsh\Fennel\Facades\Fennel;
use nickwelsh\Fennel\Services\FennelService;

test('getStringFromArray returns expected string or null', function () {
    $service = new FennelService;

    expect($service->getStringFromArray(['key' => 'value'], 'key'))->toBe('value');
    expect($service->getStringFromArray(['key' => null], 'key'))->toBeNull();
    expect($service->getStringFromArray([], 'key'))->toBeNull();
});

test('getIntFromArray returns expected integer or null', function () {
    $service = new FennelService;

    expect($service->getIntFromArray(['key' => '123'], 'key'))->toBe(123);
    expect($service->getIntFromArray(['key' => 456], 'key'))->toBe(456);
    expect($service->getIntFromArray(['key' => 'abc'], 'key'))->toBeNull();
    expect($service->getIntFromArray([], 'key'))->toBeNull();
});

test('getFloatFromArray returns expected float or null', function () {
    $service = new FennelService;

    expect($service->getFloatFromArray(['key' => '3.14'], 'key'))->toBe(3.14);
    expect($service->getFloatFromArray(['key' => 6.28], 'key'))->toBe(6.28);
    expect($service->getFloatFromArray(['key' => 'not a number'], 'key'))->toBeNull();
    expect($service->getFloatFromArray([], 'key'))->toBeNull();
});

test('transformFromCloudflareScale calculates correctly for numbers >= 1', function () {
    $service = new FennelService;

    expect($service->transformFromCloudflareScale(2.0))->toBe(50.0);
    expect($service->transformFromCloudflareScale(1))->toBe(0.0);
});

test('transformFromCloudflareScale calculates correctly for numbers < 1', function () {
    $service = new FennelService;

    // For example, 0.5 should return 100 * (0.5 - 1) = -50.
    expect($service->transformFromCloudflareScale(0.5))->toBe(-50.0);
    expect($service->transformFromCloudflareScale(0.25))->toBe(-75.0);
    expect($service->transformFromCloudflareScale(0.75))->toBe(-25.0);
});

test('getSizeRespectingAspectRatio returns correct size when only desiredWidth is set', function () {
    $service = new FennelService;

    // For an image 200x100 with desiredWidth 100:
    // Expected height = 100 / 200 * 100 = 50.
    $result = $service->getSizeRespectingAspectRatio('contain', 200, 100, 100, null);
    expect($result['width'])->toBe(100);
    expect($result['height'])->toBe(50);
});

test('getSizeRespectingAspectRatio returns correct size when only desiredHeight is set', function () {
    $service = new FennelService;

    // For an image 200x100 with desiredHeight 50:
    // Expected width = 50 / 100 * 200 = 100.
    $result = $service->getSizeRespectingAspectRatio('contain', 200, 100, null, 50);
    expect($result['width'])->toBe(100);
    expect($result['height'])->toBe(50);
});

test('getSizeRespectingAspectRatio returns correct size when both dimensions are set with "contain"', function () {
    $service = new FennelService;

    // For an image 200x100 and desired dimensions 150x150 in "contain" mode:
    // The image aspect ratio is 200/100 = 2, while desired ratio is 1.
    // It should use the desired width and calculate height = int(150/2) = 75.
    $result = $service->getSizeRespectingAspectRatio('contain', 200, 100, 150, 150);
    expect($result['width'])->toBe(150);
    expect($result['height'])->toBe(75);
});

test('getSizeRespectingAspectRatio returns correct size when both dimensions are set with "cover"', function () {
    $service = new FennelService;

    // In "cover" mode, the ratio is based on the desired dimensions.
    // For desired dimensions 150x150, we get a ratio of 1.
    // Here the branch will return width = int(150 * 1) = 150 and height = 150.
    $result = $service->getSizeRespectingAspectRatio('cover', 200, 100, 150, 150);
    expect($result['width'])->toBe(150);
    expect($result['height'])->toBe(150);
});

test('getSizeRespectingAspectRatio returns fallback image dimensions when no desired dimensions provided', function () {
    $service = new FennelService;

    // If desiredWidth and desiredHeight are both null, it should return the image dimensions.
    $result = $service->getSizeRespectingAspectRatio('contain', 200, 100, null, null);
    expect($result['width'])->toBe(200);
    expect($result['height'])->toBe(100);
});

test('getImageFormat returns provided format if valid', function () {
    // Simulate the facade call to return a valid format string
    Fennel::shouldReceive('getStringFromArray')
        ->with(['format' => 'avif'], 'format')
        ->andReturn('avif');

    $service = new FennelService;

    expect($service->getImageFormat(['format' => 'avif']))->toBe(ImageFormat::AVIF);
});

test('getImageFormat returns header based format when option is invalid', function () {
    // Simulate an invalid provided format
    Fennel::shouldReceive('getStringFromArray')
        ->with([], 'format')
        ->andReturn(null);

    // Set fallback in case header does not match
    config()->set('fennel.default_format_fallback', ImageFormat::HEIC);

    // Test Accept header: image/webp should return WEBP
    $requestWebp = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'image/webp']);
    app()->instance('request', $requestWebp);
    $service = new FennelService;
    expect($service->getImageFormat([]))->toBe(ImageFormat::WebP);

    // Test Accept header: image/avif should return AVIF
    $requestAvif = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'image/avif']);
    app()->instance('request', $requestAvif);
    expect($service->getImageFormat([]))->toBe(ImageFormat::AVIF);

    // Test Accept header: image/heic should return HEIC
    $requestHeic = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => 'image/heic, image/png']);
    app()->instance('request', $requestHeic);
    expect($service->getImageFormat([]))->toBe(ImageFormat::HEIC);
});

test('getImageFormat returns fallback format when Accept header is empty', function () {
    Fennel::shouldReceive('getStringFromArray')
        ->with([], 'format')
        ->andReturn(null);

    config()->set('fennel.default_format_fallback', ImageFormat::WebP);

    $request = Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT' => '']);
    app()->instance('request', $request);
    $service = new FennelService;

    expect($service->getImageFormat([]))->toBe(ImageFormat::WebP);
});
