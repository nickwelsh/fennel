<?php

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use nickwelsh\Fennel\Facades\Fennel;
use nickwelsh\Fennel\Services\FennelImageService;

beforeEach(function () {
    $this->imageWidth = 1920;
    $this->imageHeight = 1080;
    $this->squareSize = 100;

    if (! file_exists(public_path('images'))) {
        mkdir(public_path('images'), 0755, true);
    }

    $image = imagecreatetruecolor($this->imageWidth, $this->imageHeight);

    // Fill background white
    $white = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $white);

    // Draw 100x100 black square centered
    $black = imagecolorallocate($image, 0, 0, 0);

    $x = 0;
    $y = 0;
    imagefilledrectangle($image, $x, $y, $x + $this->squareSize, $y + $this->squareSize, $black);

    imagepng($image, public_path('images/test.png'));
    imagedestroy($image);

    config()->set('fennel.use_public_path', true);

    $this->service = Fennel::fromPath('test.png');
});

it('throws a FileNotFoundException when file does not exist', function () {
    expect(fn () => Fennel::fromPath('does-not-exist.jpg'))
        ->toThrow(FileNotFoundException::class);
});

it('returns a valid FennelImageService object when file exists', function () {
    expect($this->service)->toBeInstanceOf(FennelImageService::class);
});

it('trims the top of the image', function () {
    $this->service->trimTop(100);

    expect($this->service->imageWidth)->toBe($this->imageWidth);
    expect($this->service->imageHeight)->toBe($this->imageHeight - 100);
});

it('trims the right of the image', function () {
    $this->service->trimRight(100);

    expect($this->service->imageWidth)->toBe($this->imageWidth - 100);
    expect($this->service->imageHeight)->toBe($this->imageHeight);
});

it('trims the bottom of the image', function () {
    $this->service->trimBottom(100);

    expect($this->service->imageWidth)->toBe($this->imageWidth);
    expect($this->service->imageHeight)->toBe($this->imageHeight - 100);
});

it('trims the left of the image', function () {
    $this->service->trimLeft(100);

    expect($this->service->imageWidth)->toBe($this->imageWidth - 100);
    expect($this->service->imageHeight)->toBe($this->imageHeight);
});

it('trims the width of the image', function () {
    $this->service->trimWidth(100);

    expect($this->service->imageWidth)->toBe(100);
    expect($this->service->imageHeight)->toBe($this->imageHeight);
});

it('trims the height of the image', function () {
    $this->service->trimHeight(100);

    expect($this->service->imageWidth)->toBe($this->imageWidth);
    expect($this->service->imageHeight)->toBe(100);
});

it('trims the top, right, bottom, and left of the image', function () {
    $this->service->trim(100, 100, 100, 100);

    expect($this->service->imageWidth)->toBe($this->imageWidth - 200);
    expect($this->service->imageHeight)->toBe($this->imageHeight - 200);
});

it('trims the edges automatically', function () {
    $this->service->trimAuto();

    // Removes border areas of the image on all sides that have a similar color.
    // We have a black square in the center of the image, which is a white
    // background. We should be left with the black square, plus 1px.
    expect($this->service->imageWidth)->toBe($this->squareSize + 1);
    expect($this->service->imageHeight)->toBe($this->squareSize + 1);
});

it('can rotate the image', function () {
    $this->service->rotate(90);

    expect($this->service->imageWidth)->toBe($this->imageHeight);
    expect($this->service->imageHeight)->toBe($this->imageWidth);
});

it('can flip the image horizontally', function () {
    // Flip the image horizontally.
    $this->service->flipHorizontal();

    // Get the Intervention ImageInterface instance.
    $image = $this->service->image;

    // Calculate the starting point for the top-right 100×100 region.
    $startX = $this->imageWidth - $this->squareSize;
    $startY = 0;
    $allBlack = true;

    // Loop through each pixel in the 100×100 region.
    for ($x = $startX; $x < $this->imageWidth; $x++) {
        for ($y = $startY; $y < $this->squareSize; $y++) {
            // pickColor returns an array [R, G, B, A].
            $color = $image->pickColor($x, $y)->toArray();
            if ($color[0] !== 0 || $color[1] !== 0 || $color[2] !== 0) {
                $allBlack = false;
                break 2;
            }
        }
    }

    expect($allBlack)->toBeTrue();
});

it('can flip the image vertically', function () {
    // Flip the image vertically.
    $this->service->flipVertical();

    // Get the Intervention ImageInterface instance.
    $image = $this->service->image;

    // Calculate the starting point for the top-right 100×100 region.
    $startX = 0;
    $startY = $this->imageHeight - $this->squareSize;
    $allBlack = true;

    // Loop through each pixel in the 100×100 region.
    for ($x = $startX; $x < $this->imageWidth; $x++) {
        for ($y = $startY; $y < $this->squareSize; $y++) {
            // pickColor returns an array [R, G, B, A].
            $color = $image->pickColor($x, $y)->toArray();
            if ($color[0] !== 0 || $color[1] !== 0 || $color[2] !== 0) {
                $allBlack = false;
                break 2;
            }
        }
    }

    expect($allBlack)->toBeTrue();
});

it('can generate a query string', function () {
    $this->service->trimTop(100);
    $this->service->trimRight(100);
    $this->service->trimBottom(100);
    $this->service->trimLeft(100);
    $this->service->rotate(90);
    $this->service->flipHorizontal();
    $this->service->flipVertical();
    $this->service->brightness(0.5);
    $this->service->contrast(0.5);
    $this->service->gamma(1.2);
    $this->service->blur(10);
    $this->service->saturation(2);
    $this->service->sharpen(2);

    expect($this->service->getUrl())->toBe('http://localhost/images/test.png/trim.top=100,trim.right=100,trim.bottom=100,trim.left=100,rotate=90,flip=hv,brightness=0.5,contrast=0.5,gamma=1.2,blur=10,saturation=2,sharpen=2');
});
