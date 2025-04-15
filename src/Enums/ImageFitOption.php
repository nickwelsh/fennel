<?php

namespace nickwelsh\Fennel\Enums;

enum ImageFitOption: string
{
    case ScaleDown = 'scale-down';
    case Contain = 'contain';
    case Cover = 'cover';
    case Crop = 'crop';
    case Pad = 'pad';
}
