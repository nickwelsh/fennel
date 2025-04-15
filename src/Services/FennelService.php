<?php

namespace nickwelsh\Fennel\Services;

use Illuminate\Support\Arr;
use nickwelsh\Fennel\Enums\ImageFormat;
use nickwelsh\Fennel\Facades\Fennel;
use Throwable;

class FennelService
{
    public function __construct() {}

    /**
     * @throws Throwable
     */
    public function fromPath(string $path): FennelImageService
    {
        return new FennelImageService($path);
    }

    /**
     * Safely parse an array's value as a string.
     *
     * @param  array<string, string|null>  $array
     */
    public function getStringFromArray(array $array, string $key): ?string
    {
        $value = Arr::get($array, $key);

        if (is_null($value)) {
            return null;
        }

        return is_string($value) ? $value : null;
    }

    /**
     * Safely parse an array's value as an integer.
     *
     * @param  array<string, string|null>  $array
     */
    public function getIntFromArray(array $array, string $key): ?int
    {
        $value = Arr::get($array, $key);

        if (is_null($value)) {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * Safely parse an array's value as a float.
     *
     * @param  array<string, string|null>  $array
     */
    public function getFloatFromArray(array $array, string $key): ?float
    {
        $value = Arr::get($array, $key);

        if (is_null($value)) {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * Transform a number from Cloudflare's scale to Intervention's scale.
     */
    public function transformFromCloudflareScale(float $number): float
    {
        if ($number >= 1) {
            $number = 100 * (1 - 1 / $number);
        } else {
            $number = 100 * ($number - 1);
        }

        return $number;
    }

    /**
     * Get the width and height of an image, respecting the aspect ratio.
     *
     * @param  'contain' | 'cover'  $aspectMode
     * @return array{width: int, height: int}
     */
    public function getSizeRespectingAspectRatio(string $aspectMode, int $imageWidth, int $imageHeight, ?int $desiredWidth, ?int $desiredHeight): array
    {
        if (! is_null($desiredWidth) and is_null($desiredHeight)) {
            return [
                'width' => $desiredWidth,
                'height' => intval($desiredWidth / $imageWidth * $imageHeight),
            ];
        } elseif (! is_null($desiredHeight) and is_null($desiredWidth)) {
            return [
                'width' => intval($desiredHeight / $imageHeight * $imageWidth),
                'height' => $desiredHeight,
            ];
        } elseif (! is_null($desiredWidth) and ! is_null($desiredHeight)) {
            // aspect ratio
            $ratio = $aspectMode === 'contain' ? $imageWidth / $imageHeight : $desiredWidth / $desiredHeight;
            if ($ratio > $desiredWidth / $desiredHeight) {
                return [
                    'width' => $desiredWidth,
                    'height' => intval($desiredWidth / $ratio),
                ];
            } else {
                return [
                    'width' => intval($desiredHeight * $ratio),
                    'height' => $desiredHeight,
                ];
            }
        }

        return [
            'width' => $desiredWidth ?? $imageWidth,
            'height' => $desiredHeight ?? $imageHeight,
        ];
    }

    /**
     * Determine the image format to convert to based on the Accept header.
     *
     * @param  array<string, string|null>  $options
     */
    public function getImageFormat(array $options): ImageFormat
    {
        if ($providedFormat = ImageFormat::tryFrom(Fennel::getStringFromArray($options, 'format') ?? '')) {
            return $providedFormat;
        }

        $request = request();
        $accept = $request->header('Accept');

        if (empty($accept)) {
            $config = config('fennel.default_format_fallback');
            assert($config instanceof ImageFormat);

            return $config;
        }

        $formats = array_map(fn ($format) => strtolower($format), explode(',', $accept));

        if (in_array('image/avif', $formats)) {
            return ImageFormat::AVIF;
        }

        if (in_array('image/webp', $formats)) {
            return ImageFormat::WebP;
        }

        if (in_array('image/heic', $formats)) {
            return ImageFormat::HEIC;
        }

        $config = config('fennel.default_format_fallback');
        assert($config instanceof ImageFormat);

        return $config;
    }
}
