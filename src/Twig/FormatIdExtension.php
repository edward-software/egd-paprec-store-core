<?php
declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class FormatIdExtension extends AbstractExtension
{
    
    /**
     * @return array|TwigFilter[]
     */
    public function getFilters()
    {
        return [
            new TwigFilter('formatId', [$this, 'formatId']),
        ];
    }
    
    /**
     * @param string $id
     * @param int    $padlength
     * @param string $padstring
     * @param int    $pad_type
     *
     * @return string
     */
    public function formatId(string $id, int $padlength, $padstring = '0', $pad_type = STR_PAD_LEFT)
    {
        return str_pad($id, $padlength, $padstring, $pad_type);
    }
}
