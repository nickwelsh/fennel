<?php

namespace nickwelsh\Fennel\Http\Controllers;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use ImagickException;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use nickwelsh\Fennel\Enums\ImageFitOption;
use nickwelsh\Fennel\Facades\Fennel;
use nickwelsh\Fennel\Services\FennelImageService;
use Throwable;

class ImageController extends Controller
{
    /**
     * @throws Throwable
     */
    public function show(Request $request, string $options, string $path): Response
    {
        try {
            if (App::isProduction() && ! is_null(config('fennel.max_number_of_attempts'))) {
                $this->rateLimit($request, $path);
            }

            $image = Fennel::fromPath($path);

            $options = $this->parseOptions($options);

            $image = $this->config($image, $options);

            $image = $this->trim($image, $options);

            $image = $this->scale($image, $options);

            $image = $this->transform($image, $options);

            $image = $this->color($image, $options);

            $image = $this->filters($image, $options);

            $image->quality($this->getQuality($options));

            [$mime, $encoded] = $image->encode(Fennel::getImageFormat($options));

            return response($encoded, 200)
                ->header('Content-Type', $mime)
                ->header('Cache-Control', Config::string('fennel.cache_control'));
        } catch (Throwable) {
            throw new HttpResponseException(Redirect::to("/images/$path"));
        }
    }

    // region: utils
    /**
     * Parse the option string into an array of key-value pairs.
     *
     * @return array<string, string|null>
     */
    protected function parseOptions(string $options): array
    {
        /** @var Collection<string, string|null> $collection */
        $collection = collect(explode(',', $options))
            ->mapWithKeys(function ($opt) {
                try {
                    $parts = explode('=', $opt, 2);

                    return [$parts[0] => $parts[1]];
                } catch (Throwable) {
                    return [];
                }
            });

        /** @var array<string, string|null> $array */
        $array = $collection->toArray();

        return $array;

    }

    /**
     * Rate-limit the request based on the IP address and the image path.
     */
    protected function rateLimit(Request $request, string $path): void
    {
        $allowed = RateLimiter::attempt(
            key: 'img:'.$request->ip().':'.$path,
            maxAttempts: Config::integer('fennel.max_number_of_attempts'),
            callback: fn () => true
        );

        if (! $allowed) {
            throw new HttpResponseException(Redirect::to("/images/$path"));
        }
    }

    /**
     * Get the image from the given path.
     */
    protected function getImage(string $path): ImageInterface
    {
        if (config('fennel.use_public_path')) {
            $path = public_path("images/$path");
            abort_unless(File::exists($path), 404);

            return new ImageManager(Driver::class)->read($path);
        }

        abort_unless(Storage::disk(Config::string('fennel.disk'))->exists($path), 404);

        return new ImageManager(Driver::class)->read(Storage::disk(Config::string('fennel.disk'))->get($path));
    }

    // endregion

    // region: transforms

    /**
     * Apply settings to the image, such as removing animation, setting background color, etc.
     *
     * @param  array<string, string|null>  $options
     *
     * @link https://image.intervention.io/v3/modifying/animations#remove-animation
     * @link https://image.intervention.io/v3/basics/colors#merge-transparent-areas-with-color
     * @link https://image.intervention.io/v3/basics/colors#set-the-blending-color
     */
    private function config(FennelImageService $image, array $options): FennelImageService
    {
        if (Arr::has($options, 'anim')) {
            $shouldAnim = (bool) Arr::get($options, 'anim', Config::boolean('fennel.preserve_animation_frames'));
            $image->animate($shouldAnim);
        }

        if (Arr::has($options, 'background')) {
            if (is_string(Fennel::getStringFromArray($options, 'background'))) {
                $image->background(Fennel::getStringFromArray($options, 'background'));
            }
        }

        if (Arr::has($options, 'dpr')) {
            if (is_int(Fennel::getIntFromArray($options, 'dpr'))) {
                $image->dpr(Fennel::getIntFromArray($options, 'dpr'));
            }
        }

        if (Arr::has($options, 'gravity')) {
            if (is_string(Fennel::getStringFromArray($options, 'gravity'))) {
                $image->position(Fennel::getStringFromArray($options, 'gravity'));
            }
        }

        return $image;
    }

    /**
     * Trim the image by removing a specified number of pixels from the top, right, bottom, and/or left.
     *
     * @note Intervention does have a `trim()` method, but Cloudflare's trim is for cropping an image.
     *
     * @param  array<string, string|null>  $options
     *
     * @link https://image.intervention.io/v3/modifying/resizing#crop-image
     */
    protected function trim(FennelImageService $image, array $options): FennelImageService
    {

        if (Arr::has($options, 'trim')) {
            $trim = Fennel::getStringFromArray($options, 'trim') ?? '0;0;0;0';

            //            [$top, $right, $bottom, $left] = explode(';', $trim);
            $trims = explode(';', $trim);

            if ($trims[0] === 'auto') {
                $image->trimAuto(tolerance: $trims[1] ? (int) $trims[1] : 0);

                return $image;
            }

            $top = (int) $trims[0];
            $right = (int) $trims[1];
            $bottom = (int) $trims[2];
            $left = (int) $trims[3];

            $image->trim(top: $top, right: $right, bottom: $bottom, left: $left);

            return $image;
        }

        if (Arr::has($options, 'trim.top')) {
            $image->trimTop(Fennel::getIntFromArray($options, 'trim.top') ?? 0);
        }

        if (Arr::has($options, 'trim.right')) {
            $image->trimRight(Fennel::getIntFromArray($options, 'trim.right') ?? 0);
        }

        if (Arr::has($options, 'trim.bottom')) {
            $image->trimBottom(Fennel::getIntFromArray($options, 'trim.bottom') ?? 0);
        }

        if (Arr::has($options, 'trim.left')) {
            $image->trimLeft(Fennel::getIntFromArray($options, 'trim.left') ?? 0);
        }

        if (Arr::has($options, 'trim.width')) {
            $image->trimWidth(Fennel::getIntFromArray($options, 'trim.width') ?? 0);
        }

        if (Arr::has($options, 'trim.height')) {
            $image->trimHeight(Fennel::getIntFromArray($options, 'trim.height') ?? 0);
        }

        return $image;
    }

    /**
     * Scale the image to the given width and/or height.
     *
     * @note Cloudflare's scale methods are slightly different from Intervention's.
     *
     * @param  array<string, string|null>  $options
     *
     * @link https://image.intervention.io/v3/modifying/resizing
     */
    protected function scale(FennelImageService $image, array $options): FennelImageService
    {
        // Are we even scaling?
        if (! Arr::hasAny($options, ['width', 'height', 'dpr', 'fit'])) {
            return $image;
        }

        // We need to have a width, height, or both
        if (! Arr::hasAny($options, ['width', 'height'])) {
            return $image;
        }

        $width = Fennel::getIntFromArray($options, 'width') ?? null;
        $height = Fennel::getIntFromArray($options, 'height') ?? null;

        // We default to scale down to prevent up-scaling images. A bad actor could
        // send a request to scale up an image to a ridiculous size.
        $fitOption = Fennel::getStringFromArray($options, 'fit');
        $scale = ImageFitOption::tryFrom($fitOption ?? '') ?? ImageFitOption::ScaleDown;

        $args = [];
        if (! is_null($width)) {
            $args['width'] = min($width, $image->getImage()->width());
        }
        if (! is_null($height)) {
            $args['height'] = min($height, $image->getImage()->height());
        }

        switch ($scale) {
            case ImageFitOption::ScaleDown:
                $image->scaleDown(...$args);
                break;
            case ImageFitOption::Contain:
                $image->contain(width: min($width ?? $image->getImage()->width(), $image->getImage()->width()), height: min($height ?? $image->getImage()->height(), $image->getImage()->height()));
                break;
            case ImageFitOption::Cover:
                $image->cover(...$args);
                break;
            case ImageFitOption::Crop:
                $image->crop(width: min($width, $image->getImage()->width()), height: min($height, $image->getImage()->height()));
                break;
            case ImageFitOption::Pad:
                $image->pad(...$args);
                break;
        }

        return $image;
    }

    /**
     * Apply transformations to the image, such as rotation, flipping, etc.
     *
     * @param  array<string, string|null>  $options
     *
     * @link https://image.intervention.io/v3/modifying/effects#image-rotation
     * @link https://image.intervention.io/v3/modifying/effects#mirror-image-vertically
     * @link https://image.intervention.io/v3/modifying/effects#mirror-image-horizontally
     */
    protected function transform(FennelImageService $image, array $options): FennelImageService
    {
        if (Arr::has($options, 'rotate')) {
            $image->rotate(Fennel::getIntFromArray($options, 'rotate') ?? 0);
        }

        if (Arr::has($options, 'flip')) {
            switch ($options['flip']) {
                case 'h':
                    $image->flipHorizontal();
                    break;
                case 'v':
                    $image->flipVertical();
                    break;
                case 'hv':
                    $image->flipHorizontal()->flipVertical();
                    break;
            }
        }

        return $image;
    }

    /**
     * Apply color transformations to the image, such as brightness, contrast, gamma, etc.
     *
     * @param  array<string, string|null>  $options
     *
     * @throws ImagickException
     *
     * @link https://image.intervention.io/v3/modifying/effects#change-the-contrast
     * @link https://image.intervention.io/v3/modifying/effects#gamma-correction
     * @link https://www.php.net/manual/en/imagick.brightnesscontrastimage.php
     */
    protected function color(FennelImageService $image, array $options): FennelImageService
    {
        if (Arr::has($options, 'brightness')) {
            $image->brightness(Fennel::getFloatFromArray($options, 'brightness') ?? 1.0);
        }

        if (Arr::has($options, 'contrast')) {
            $image->contrast(Fennel::getFloatFromArray($options, 'contrast') ?? 1.0);
        }

        if (Arr::has($options, 'gamma')) {
            $image->gamma(Fennel::getFloatFromArray($options, 'gamma') ?? 1.0);
        }

        return $image;
    }

    /**
     * Apply filters to the image, such as blur, saturation, sharpen, etc.
     *
     * @param  array<string, string|null>  $options
     *
     * @throws ImagickException
     *
     * @link https://image.intervention.io/v3/modifying/effects#sharpening-effect
     * @link https://image.intervention.io/v3/modifying/effects#blur-effect
     */
    private function filters(FennelImageService $image, array $options): FennelImageService
    {
        if (Arr::has($options, 'blur')) {
            $image->blur(Fennel::getIntFromArray($options, 'blur') ?? 0);
        }

        if (Arr::has($options, 'saturation')) {
            $image->saturation(Fennel::getFloatFromArray($options, 'saturation') ?? 100.0);
        }

        if (Arr::has($options, 'sharpen')) {
            $image->sharpen(Fennel::getIntFromArray($options, 'sharpen') ?? 0);
        }

        return $image;
    }

    /**
     * Get the quality to use for the image. Can determine if the connection is slow and use a different quality.
     *
     * @param  array<string, string|null>  $options
     */
    private function getQuality(array $options): int
    {
        $quality = Fennel::getIntFromArray($options, 'quality') ?? 100;

        if (Arr::has($options, 'slow-connection-quality') || config('fennel.slow_connection_quality')) {
            $slowConnectionQuality = Fennel::getIntFromArray($options, 'slow-connection-quality') ?? Config::integer('fennel.slow_connection_quality');

            $request = request();
            $rtt = (int) $request->header('RTT');
            $saveData = (string) $request->header('Save-Data');
            $ect = (string) $request->header('ECT');
            $downlink = (int) $request->header('Downlink');

            if ($rtt > 150 || $saveData === 'on' || $ect === 'slow-2g' || $ect === '2g' || $ect === '3g' || $downlink < 5) {
                $quality = $slowConnectionQuality;
            }
        }

        return $quality;
    }

    // endregion

}
