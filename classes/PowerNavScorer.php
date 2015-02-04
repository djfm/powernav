<?php

class PowerNavScorer
{
    public function __construct($query)
    {

    }

    /**
     * Extract letter counts, pairs, words, distr.
     */
    private function prepare($string)
    {
        $words = array();
        $chars = array();
        $pairs = array();
        $distr = array();

        $m = array();
        preg_match_all('/(?:\p{L}|\d)+/u', mb_strtolower($string, 'UTF-8'), $m);
        $wordStrings = $m[0];

        foreach ($wordStrings as $wordString)
        {
            $bits = preg_split('//u', $wordString, -1, PREG_SPLIT_NO_EMPTY);
            $words[] = $bits;
            $chars = array_merge($chars, $bits);
        }

        for ($i = 0; $i < count($chars); ++$i)
        {
            $c = $chars[$i];

            for ($j = $i+1; $j < count($chars) - 1; ++$j)
            {
                $pair = $c.$chars[$j];
                $dist = 1 / ($j - $i);

                if (!array_key_exists($pair, $pairs) || $dist < $pairs[$pair])
                {
                    $pairs[$pair] = $dist;
                }
            }

            if (!isset($distr[$chars[$i]]))
            {
                $distr[$c] = 0;
            }

            ++$distr[$c];
        }

        return array(
            'words' => $words,
            'pairs' => $pairs,
            'chars' => $chars,
            'distr' => $distr
        );
    }

    public function score($string)
    {
        return 0;
    }
}
