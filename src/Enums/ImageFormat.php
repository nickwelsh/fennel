<?php

namespace nickwelsh\Fennel\Enums;

enum ImageFormat: string
{
    case AVIF = 'avif';
    case WebP = 'webp';
    case HEIC = 'heic';
    case JPEG = 'jpeg';
    case BaselineJPEG = 'baseline-jpeg';
    case TIFF = 'tiff';
    case PNG = 'png';
    case GIF = 'gif';
    case BMP = 'bmp';
}
