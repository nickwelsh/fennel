<?php

namespace nickwelsh\Fennel\View\Components;

use BackedEnum;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use InvalidArgumentException;
use nickwelsh\Fennel\Enums\ImageFitOption;
use nickwelsh\Fennel\Enums\ImageFormat;

class Image extends Component
{
    public string $src;

    public string $alt;

    public ?bool $anim;

    public ?string $background;

    public ?int $blur;

    public ?float $brightness;

    public ?float $contrast;

    public ?int $dpr;

    public ?ImageFitOption $fit;

    /** @var 'h'|'v'|'hv'|null */
    public ?string $flip;

    public ?ImageFormat $format;

    public ?float $gamma;

    public ?string $gravity;

    public ?int $height;

    public ?int $quality;

    public ?int $rotate;

    public ?float $saturation;

    public ?float $sharpen;

    public ?int $slowConnectionQuality;

    public ?string $trim;

    public ?int $trimWidth;

    public ?int $trimHeight;

    public ?int $trimLeft;

    public ?int $trimTop;

    public ?int $width;

    public string $paramString;

    public function __construct(
        string $src,
        string $alt = '',
        bool $anim = false,
        ?string $background = null,
        ?int $blur = null,
        ?float $brightness = null,
        ?float $contrast = null,
        ?int $dpr = null,
        ?ImageFitOption $fit = null,
        /** @var 'h'|'v'|'hv'|null $flip */
        ?string $flip = null,
        ?ImageFormat $format = null,
        ?float $gamma = null,
        ?string $gravity = null,
        ?int $height = null,
        ?int $quality = null,
        ?int $rotate = null,
        ?float $saturation = null,
        ?float $sharpen = null,
        ?int $slowConnectionQuality = null,
        ?string $trim = null,
        ?int $trimWidth = null,
        ?int $trimHeight = null,
        ?int $trimLeft = null,
        ?int $trimTop = null,
        ?int $width = null,
    ) {
        $this->src = $src;
        $this->alt = $alt;
        $this->anim = $anim;
        $this->background = $background;
        $this->blur = $blur;
        $this->brightness = $brightness;
        $this->contrast = $contrast;
        $this->dpr = $dpr;
        $this->fit = $fit;
        if (! in_array($flip, ['h', 'v', 'hv', null], true)) {
            throw new InvalidArgumentException("Invalid flip value: $flip");
        }
        $this->flip = $flip;
        $this->format = $format;
        $this->gamma = $gamma;
        $this->gravity = $gravity;
        $this->height = $height;
        $this->quality = $quality;
        $this->rotate = $rotate;
        $this->saturation = $saturation;
        $this->sharpen = $sharpen;
        $this->slowConnectionQuality = $slowConnectionQuality;
        $this->trim = $trim;
        $this->trimWidth = $trimWidth;
        $this->trimHeight = $trimHeight;
        $this->trimLeft = $trimLeft;
        $this->trimTop = $trimTop;
        $this->width = $width;

        // Build the parameter string for the image URL.
        $urlParameters = [
            'anim' => $anim,
            'background' => $background,
            'blur' => $blur,
            'brightness' => $brightness,
            'contrast' => $contrast,
            'dpr' => $dpr,
            'fit' => $fit,
            'flip' => $flip,
            'format' => $format,
            'gamma' => $gamma,
            'gravity' => $gravity,
            'height' => $height,
            'quality' => $quality,
            'rotate' => $rotate,
            'saturation' => $saturation,
            'sharpen' => $sharpen,
            'slow-connection-quality' => $slowConnectionQuality,
            'trim' => $trim,
            'trim.width' => $trimWidth,
            'trim.height' => $trimHeight,
            'trim.left' => $trimLeft,
            'trim.top' => $trimTop,
            'width' => $width,
        ];

        $params = [];
        foreach ($urlParameters as $key => $value) {
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

        $this->paramString = implode(',', $params);
    }

    public function render(): View
    {
        /** @phpstan-ignore-next-line */
        return view('fennel::components.image');
    }
}
