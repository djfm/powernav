<?php

class PowerNavScorer
{
    private $query;
    private $preparedQuery;

    public function __construct($query)
    {
        $this->query = $query;
        $this->preparedQuery = $this->prepare($query);
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

            if ($i < count($chars) - 1)
            {
                $pair = $c . $chars[$i + 1];

                if (!array_key_exists($pair, $pairs))
                {
                    $pairs[$pair] = 1;
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
        $preparedString = $this->prepare($string);

        $commonChars = 0;
        $totalChars = count($this->preparedQuery['chars']);
        foreach ($this->preparedQuery['distr'] as $char => $count)
        {
            if (isset($preparedString['distr'][$char]))
            {
                $commonChars += min($count, $preparedString['distr'][$char]);
            }
        }
        $commonCharsScore = $commonChars / $totalChars;

        $commonPairs = 0;
        $totalPairs = count($this->preparedQuery['pairs']);
        foreach ($this->preparedQuery['pairs'] as $pair)
        {
            if (isset($preparedString['pairs'][$pair]))
            {
                $commonPairs += 1;
            }
        }
        $commonPairsScore = $commonPairs / $totalPairs;

        $OKWords = 0;
        foreach ($this->preparedQuery['words'] as $qw)
        {
            foreach ($preparedString['words'] as $sw)
            {
                if ($qw[0] === $sw[0] && end($qw) === end($sw))
                {
                    ++$OKWords;
                }
            }
        }
        $OKWordsScore = $OKWords / count($this->preparedQuery['words']);

        return ($commonCharsScore + $commonPairsScore + $OKWordsScore) / 3;
    }
}
