<?php

namespace nickwelsh\Fennel\Services;

use BackedEnum;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Imagick;
use ImagickException;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\Encoders\AvifEncoder;
use Intervention\Image\Encoders\BmpEncoder;
use Intervention\Image\Encoders\GifEncoder;
use Intervention\Image\Encoders\HeicEncoder;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\TiffEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\EncodedImageInterface;
use Intervention\Image\Interfaces\ImageInterface;
use nickwelsh\Fennel\Enums\ImageFitOption;
use nickwelsh\Fennel\Enums\ImageFormat;
use nickwelsh\Fennel\Facades\Fennel;
use Throwable;

class FennelImageService
{
    private string $path;

    private ImageInterface $image;

    private int $dpr;

    /** @var array<string, string | bool | float | int | null| ImageFitOption | ImageFormat> */
    private array $urlParameters;

    private int $quality;

    private string $position;

    private string $background;

    /**
     * @throws Throwable
     */
    public function __construct(string $path)
    {
        $this->path = $path;

        if (config('fennel.use_public_path')) {
            $path = public_path("images/$path");
            throw_unless(File::exists($path), FileNotFoundException::class);

            $this->image = (new ImageManager(Driver::class))->read($path);
        } else {
            throw_unless(Storage::disk(Config::string('fennel.disk'))->exists($path), FileNotFoundException::class);
            $this->image = (new ImageManager(Driver::class))->read(Storage::disk(Config::string('fennel.disk'))->get($path));
        }

        $this->dpr = 1;
        $this->quality = Config::integer('fennel.default_quality');
        $this->urlParameters = [];
        $this->background = '#ffffff';

    }

    // region: Getters
    public function getImage(): ImageInterface
    {
        return $this->image;
    }

    // region: Config
    public function dpr(int $dpr): self
    {
        $this->dpr = $dpr;
        $this->addQueryString('dpr', $dpr);

        return $this;
    }

    public function quality(int $quality): self
    {
        $this->addQueryString('quality', $quality);
        $this->quality = $quality;

        return $this;
    }

    public function animate(bool $shouldAnimate): self
    {
        $this->addQueryString('anim', $shouldAnimate);

        if ($this->image->isAnimated() && ! $shouldAnimate) {
            $this->image->removeAnimation();
        }

        return $this;
    }

    public function background(string $color): self
    {
        $this->addQueryString('background', $color);

        $this->image->blendTransparency($color);
        $this->image->setBlendingColor($color);
        $this->background = $color;

        return $this;
    }

    public function position(string $position): self
    {
        $this->addQueryString('gravity', $position);
        $this->position = $position;

        return $this;
    }

    // endregion

    // region: Helpers

    public function getUrl(): string
    {
        $params = [];
        foreach ($this->urlParameters as $key => $value) {
            if ($value !== null && $value !== false && $value !== '') {
                // Convert a boolean true to 1.
                if ($value === true) {
                    $value = 1;
                }

                if ($value instanceof BackedEnum) {
                    $value = $value->value;
                }

                $params[] = "$key=$value";
            }
        }

        $paramString = implode(',', $params);

        return route('fennel.handle', [$this->path, $paramString]);
    }

    /** @return array{0: string, 1: EncodedImageInterface} */
    public function encode(ImageFormat $format): array
    {
        [$mime, $encoder] = match ($format) {
            ImageFormat::AVIF => ['image/avif', new AvifEncoder(quality: $this->quality, strip: Config::boolean('fennel.strip_metadata'))],
            ImageFormat::WebP => ['image/webp', new WebpEncoder(quality: $this->quality, strip: Config::boolean('fennel.strip_metadata'))],
            ImageFormat::HEIC => ['image/heic', new HeicEncoder(quality: $this->quality, strip: Config::boolean('fennel.strip_metadata'))],
            ImageFormat::JPEG => ['image/jpeg', new JpegEncoder(quality: $this->quality, progressive: true, strip: Config::boolean('fennel.strip_metadata'))],
            ImageFormat::BaselineJPEG => ['image/jpeg', new JpegEncoder(quality: $this->quality, progressive: false, strip: Config::boolean('fennel.strip_metadata'))],
            ImageFormat::TIFF => ['image/tiff', new TiffEncoder(quality: $this->quality, strip: Config::boolean('fennel.strip_metadata'))],
            ImageFormat::PNG => ['image/png', new PngEncoder],
            ImageFormat::GIF => ['image/gif', new GifEncoder],
            ImageFormat::BMP => ['image/bmp', new BmpEncoder],
        };

        return [$mime, $this->image->encode($encoder)];
    }

    // endregion

    // region: Transforms

    // Trim
    public function trimTop(int $top): self
    {
        $top = ($top * $this->dpr);
        $this->image->crop(width: $this->image->width(), height: $this->image->height() - $top, position: 'bottom');
        $this->addQueryString('trim.top', $top);

        return $this;
    }

    public function trimRight(int $right): self
    {
        $right = ($right * $this->dpr);
        $this->image->crop(width: $this->image->width() - $right, height: $this->image->height());
        $this->addQueryString('trim.right', $right);

        return $this;
    }

    public function trimBottom(int $bottom): self
    {
        $bottom = ($bottom * $this->dpr);
        $this->image->crop(width: $this->image->width(), height: $this->image->height() - $bottom);
        $this->addQueryString('trim.bottom', $bottom);

        return $this;
    }

    public function trimLeft(int $left): self
    {
        $left = ($left * $this->dpr);
        $this->image->crop(width: $this->image->width() - $left, height: $this->image->height(), position: 'top-right');
        $this->addQueryString('trim.left', $left);

        return $this;
    }

    public function trimWidth(int $width): self
    {
        $width = ($width * $this->dpr);
        $this->image->crop(width: $width, height: $this->image->height(), position: $this->position ?? 'top-left');
        $this->addQueryString('trim.width', $width);

        return $this;
    }

    public function trimHeight(int $height): self
    {
        $height = ($height * $this->dpr);
        $this->image->crop(width: $this->image->width(), height: $height, position: $this->position ?? 'top-left');
        $this->addQueryString('trim.height', $height);

        return $this;
    }

    public function trim(int $top, int $right, int $bottom, int $left): self
    {
        $this->trimTop($top);
        $this->trimRight($right);
        $this->trimBottom($bottom);
        $this->trimLeft($left);

        $this->removeQueryString('trim.top');
        $this->removeQueryString('trim.right');
        $this->removeQueryString('trim.bottom');
        $this->removeQueryString('trim.left');

        $this->addQueryString('trim', "$top;$right;$bottom;$left");

        return $this;
    }

    public function trimAuto(int $tolerance = 0): self
    {
        $this->image->trim(tolerance: $tolerance);
        $this->addQueryString('trim', 'auto;$tolerance');

        return $this;
    }

    // Scale
    public function scaleDown(?int $width = null, ?int $height = null): self
    {
        $args = [];
        if (! is_null($width)) {
            $args['width'] = $width * $this->dpr;
            $this->addQueryString('width', $width);
        }
        if (! is_null($height)) {
            $args['height'] = $height;
            $this->addQueryString('height', $height);
        }

        $this->image->scaleDown(...$args);
        $this->addQueryString('fit', ImageFitOption::ScaleDown);

        return $this;
    }

    public function contain(int $width, int $height): self
    {
        $this->addQueryString('fit', ImageFitOption::Contain);
        $this->addQueryString('width', $width);
        $this->addQueryString('height', $height);

        $size = Fennel::getSizeRespectingAspectRatio('contain', $this->image->width(), $this->image->height(), $width, $height);

        $this->image->contain(width: $size['width'], height: $size['height'], background: $this->background, position: $this->position ?? 'center');

        return $this;
    }

    public function cover(?int $width = null, ?int $height = null): self
    {

        $this->addQueryString('fit', ImageFitOption::Cover);
        $this->addQueryString('width', $width);
        $this->addQueryString('height', $height);

        $size = Fennel::getSizeRespectingAspectRatio('cover', $this->image->width(), $this->image->height(), $width, $height);
        $this->image->cover(width: $size['width'], height: $size['height'], position: $this->position ?? 'center');

        return $this;
    }

    public function crop(?int $width = null, ?int $height = null): self
    {
        $imageWidth = $this->image->width();
        $imageHeight = $this->image->height();

        if (isset($width)) {
            $this->addQueryString('width', $width);
        }
        if (isset($height)) {
            $this->addQueryString('height', $height);
        }
        $this->addQueryString('fit', ImageFitOption::Crop);

        $size = Fennel::getSizeRespectingAspectRatio('cover', $this->image->width(), $this->image->height(), $width, $height);

        if ($imageWidth > $width || $imageHeight > $height) {
            $this->image->cover(width: $size['width'], height: $size['height'], position: $this->position ?? 'center');
        } else {
            $this->image->crop(width: $width ?? $size['width'], height: $height ?? $size['height'], position: $this->position ?? 'center');
        }

        return $this;
    }

    public function pad(?int $width = null, ?int $height = null): self
    {
        $width = $width ?? $this->image->width();
        $height = $height ?? $this->image->height();

        $this->addQueryString('fit', ImageFitOption::Pad);
        $this->addQueryString('width', $width);
        $this->addQueryString('height', $height);

        $this->image->pad($width, $height, background: $this->background);

        return $this;
    }

    // Transform
    public function rotate(int $degrees): self
    {
        $this->image->rotate($degrees);
        $this->addQueryString('rotate', $degrees);

        return $this;
    }

    public function flipVertical(): self
    {
        $this->image->flip();
        $alreadyVertical = $this->getQueryString('flip') === 'v';
        if ($alreadyVertical) {
            $this->addQueryString('flip', 'hv');
        } else {
            $this->addQueryString('flip', 'h');
        }

        return $this;
    }

    public function flipHorizontal(): self
    {
        $this->image->flop();
        $alreadyHorizontal = $this->getQueryString('flip') === 'h';
        if ($alreadyHorizontal) {
            $this->addQueryString('flip', 'hv');
        } else {
            $this->addQueryString('flip', 'v');
        }

        return $this;
    }

    // Color

    /**
     * @throws ImagickException
     */
    public function brightness(float $brightness): self
    {
        $this->addQueryString('brightness', $brightness);

        $brightness = Fennel::transformFromCloudflareScale($brightness);

        /** @var Imagick $imagick */
        $imagick = $this->image->core()->native();
        $contrast = intval(max(-100, min(0, $brightness)));

        $imagick->brightnessContrastImage($brightness, $contrast);

        if ($brightness >= 0) {
            $gamma = min(1, (100 - $brightness) / 100);
        } else {
            if (abs($brightness) >= 50) {
                $scaled = 100 - (abs($brightness) - 50) * 2;
            } else {
                $scaled = abs((abs($brightness) - 50) * 2);
            }
            $gamma = ($scaled / 100);
        }

        $this->image->gamma($gamma);

        return $this;
    }

    /**
     * @throws ImagickException
     */
    public function contrast(float $contrast): self
    {
        $this->addQueryString('contrast', $contrast);

        $contrast = Fennel::transformFromCloudflareScale($contrast);
        /** @var Imagick $imagick */
        $imagick = $this->image->core()->native();
        $imagick->brightnessContrastImage(0, $contrast);

        return $this;
    }

    public function gamma(float $gamma): self
    {
        $this->addQueryString('gamma', $gamma);

        $this->image->gamma($gamma);

        return $this;
    }

    // Filters
    public function blur(int $blur): self
    {
        $this->addQueryString('blur', $blur);

        $this->image->blur($blur);

        return $this;
    }

    /**
     * @throws ImagickException
     */
    public function saturation(float $saturation): self
    {
        $this->addQueryString('saturation', $saturation);

        /** @var Imagick $imagick */
        $imagick = $this->image->core()->native();
        $imagick->modulateImage(100, $saturation * 100, 100);

        return $this;
    }

    public function sharpen(int $sharpen): self
    {
        $this->addQueryString('sharpen', $sharpen);

        $this->image->sharpen($sharpen * 10);

        return $this;
    }

    // endregion

    // region: Private Helpers
    /** @param string | bool | float | int | null| ImageFitOption | ImageFormat $value */
    private function addQueryString(string $key, mixed $value): void
    {
        $this->urlParameters[$key] = $value;
    }

    private function removeQueryString(string $key): void
    {
        unset($this->urlParameters[$key]);
    }

    private function getQueryString(string $key): mixed
    {
        if (Arr::has($this->urlParameters, $key)) {
            return $this->urlParameters[$key];
        }

        return null;
    }
    // endregion

}
