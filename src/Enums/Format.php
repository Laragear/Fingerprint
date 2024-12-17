<?php

namespace Laragear\Fingerprint\Enums;

enum Format: string
{
    case AsRaw = 'raw';
    case AsHex = 'hex';
    case AsBase64 = 'base64';
    case AsBase64UrlSafe = 'base64-url-safe';
}
