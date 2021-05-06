<?php
declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use NumberFormatter;

/**
 * Class NumberManager
 *
 * @package App\Service
 */
class NumberManager
{
    private $em;
    
    
    /**
     * NumberManager constructor.
     *
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    
    
    /**
     * @param $amount , montant en centime
     * @param $currency , si null on n'affiche pas le sympbole, sinon on affiche le symbole, si currency = PERCENTAGE on affiche %
     * @param $locale
     *
     * @return string
     */
    public function formatAmount($amount, $currency, $locale)
    {
        if ($currency) {
            if ($currency === 'PERCENTAGE') {
                $fmt = numfmt_create($locale, NumberFormatter::PERCENT);
                $fmt->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
                
                return numfmt_format($fmt, $amount / 100 / 100);
            }
            
            return twig_localized_currency_filter($amount / 100, $currency, $locale);
        }
        
        $fmt = numfmt_create($locale, NumberFormatter::DECIMAL);
        $fmt->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
        
        return numfmt_format($fmt, $this->denormalize($amount));
    }
    
    /**
     * Format simplement un nombre qui a été normalisé avec normalize15
     *
     * @param        $amount
     * @param string $locale
     *
     * @return false|string
     */
    public function formatAmount15($amount, string $locale)
    {
        $fmt = numfmt_create($locale, NumberFormatter::DECIMAL);
        $fmt->setAttribute(NumberFormatter::FRACTION_DIGITS, 15);
        
        return numfmt_format($fmt, $this->denormalize15($amount));
    }
    
    /**
     * Prend un number en paramètre et retourne sa valeur divisée par 100
     * Pour récupérer les nombres stockés en base et les afficher
     *
     * @param $value
     */
    public function denormalize($value)
    {
        if ($value === 0) {
            return 0;
        }
        
        if ($value == null || $value == '') {
            return null;
        }
        
        return $value / 100;
    }
    
    /**
     * Prend un string en paramètre, le transforme en nombre et le multplie par 100
     * Pour stocker les nombres en base
     *
     * @param $value
     *
     * @return number
     */
    public function normalize($value)
    {
        if ($value === 0) {
            return 0;
        }
        
        if ($value == null || $value == '') {
            return null;
        }
        $value = str_replace(',', '.', $value);
        
        return round($value * 100);
    }
    
    /**
     * Utilisé pour les coefficients qui ont un grand nombre de décimales
     *
     * Prend un number en paramètre et retourne sa valeur divisée par 1000000000000000
     * Pour récupérer les nombres stockés en base et les afficher
     *
     * @param $value
     *
     * @return number
     */
    public function denormalize15($value)
    {
        if ($value === 0) {
            return 0;
        }
        
        if ($value == null || $value == '') {
            return null;
        }
        
        return $value / 1000000000000000;
    }
    
    /**
     * Utilisé pour les coefficients qui ont un grand nombre de décimales
     *
     * Prend un string en paramètre, le transforme en nombre et le multplie par 1000000000000000
     * Pour stocker les nombres en base
     *
     * @param $value
     *
     * @return number
     */
    public function normalize15($value)
    {
        if ($value === 0) {
            return 0;
        }
        
        if ($value == null || $value == '') {
            return null;
        }
        
        $value = str_replace(',', '.', $value);
        
        return round($value * 1000000000000000);
    }
    
    /***
     * Format un identifiant en ajoutant des 0 jusqu'a atteindre la taille voulue
     *
     * @param $id
     * @param $padlength
     * @param int $padstring
     * @param int $pad_type
     *
     * @return string
     */
    public function formatId($id, $padlength, $padstring = 0, $pad_type = STR_PAD_LEFT)
    {
        return str_pad($id, $padlength, $padstring, $pad_type);
    }
    
}
