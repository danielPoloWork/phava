<?php

namespace Java\Util\Map;

final class ProxyEntry {
    public static function comparingByKey(): callable {
        return Entry::comparingByKey();
    }

    public static function comparingByValue(): callable {
        return Entry::comparingByValue();
    }
}