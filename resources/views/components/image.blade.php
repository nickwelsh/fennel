<img
    src="/images/{{ $paramString ? $paramString . '/' : '' }}{{ ltrim($src, '/') }}"
    alt="{{ $alt }}"
    @if($width) width="{{ $width }}" @endif
    @if($height) height="{{ $height }}" @endif
    {{ $attributes }}
>
