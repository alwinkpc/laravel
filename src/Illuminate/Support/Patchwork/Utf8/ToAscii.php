<?php

namespace Illuminate\Support\Patchwork\Utf8;

use Normalizer as n;

/**
 * Support class that avoids string manipulation deprecated libraries.
 */
class ToAscii
{
    /**
     * Converts a string to ASCII.
     * 
     * @param string $text         Text to replace
     * @param char   $replacement  Fallback char
	 * @see Patchwork\Utf8::toAscii
     */
    public static function transform($text, $replacement = '?')
    {
        if (preg_match("/[\x80-\xFF]/", $text)) {
            static $translitExtra = array();
            $translitExtra or $translitExtra = self::getData('translit_extra');
    
            $text  = n::normalize($text, n::NFKC);
            $glibc = 'glibc' === ICONV_IMPL;
    
            preg_match_all('/./u', $text, $text);
            foreach ($text[0] as &$c) {
                if (!isset($c[1])) {
                    continue;
                }
    
                if ($glibc) {
                    $t = iconv('UTF-8', 'ASCII//TRANSLIT', $c);
                } else {
                    $t = iconv('UTF-8', 'ASCII//IGNORE//TRANSLIT', $c);
    
                    if (! isset($t[0])) {
                        $t = '?';
                    } elseif (isset($t[1])) {
                        $t = ltrim($t, '\'`"^~');
                    }
                }
    
                if ('?' === $t) {
                    if (isset($translitExtra[$c])) {
                        $t = $translitExtra[$c];
                    } else {
                        $t = n::normalize($c, n::NFD);
    
                        if ($t[0] < "\x80") {
                            $t = $t[0];
                        } else {
                            $t = $replacement;
                        }
                    }
                }
    
                $c = $t;
            }
    
            $text = implode('', $text[0]);
        }

        return $text;
    }

    /**
     * Obtiene el archivo soporte translit.
     * 
     * @return string
     */
    private static function getData($file)
    {
        $file = __DIR__ . '/data/' . $file . '.ser';
        if (file_exists($file)) {
            return unserialize(file_get_contents($file));
        } else {
            return false;
        }
    }
}
