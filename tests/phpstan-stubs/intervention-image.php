<?php

namespace Intervention\Image\Drivers\Gd {
    if (! class_exists(Driver::class)) {
        class Driver {}
    }
}

namespace Intervention\Image\Drivers\Imagick {
    if (! class_exists(Driver::class)) {
        class Driver {}
    }
}

namespace Intervention\Image\Encoders {
    if (! class_exists(AvifEncoder::class)) {
        class AvifEncoder
        {
            public function __construct(public int $quality = 65) {}
        }
    }

    if (! class_exists(WebpEncoder::class)) {
        class WebpEncoder
        {
            public function __construct(public int $quality = 85) {}
        }
    }
}

namespace Intervention\Image {
    if (! class_exists(ImageManager::class)) {
        class ImageManager
        {
            public function __construct(public mixed $driver = null) {}

            public function read(string $path): mixed
            {
                return null;
            }
        }
    }
}
