<?php

namespace ProcessConversation;

class ProcessConversation {

    /**
     * [$data description]
     * @var [type]
     */
    private $data;

    /**
     * [$lcData description]
     * @var [type]
     */
    private $lcData;

    /**
     * [$wordCount description]
     * @var [type]
     */
    private $wordCount;

    /**
     * [$linkWords description]
     * @var [type]
     */
    private $linkWords;

    /**
     * [__construct description]
     * @param string $data [description]
     */
    public function __construct($data = '') {
        $lcData = strtolower($data);

        $this->data = $data;
        $this->wordCount = count(explode(' ', $lcData));
        $this->linkWords = preg_split('/[\s]+/', $lcData); // for link finding and third part of date
        $this->lcData = preg_split('/[\s]+/', $lcData);

        for ($i=0;$i<count($this->lcData);$i++) {
            $this->lcData[$i] = $this->_replace($this->lcData[$i], ' @w@@n@,@w@@n@"@w@');
            $this->lcData[$i] = str_replace('?', '', $this->lcData[$i]);
        }
    }

    /**
     * [wordCount description]
     * @return [type] [description]
     */
    public function wordCount()
    {
        return $this->wordCount;
    }

    /**
     * [checkPhoneNumbers description]
     * @return [type]       [description]
     */
    public function checkPhoneNumbers()
    {
        $numbers = array();

        foreach ($this->lcData as $data) {
            $data = $this->_replace($data, '-@w@');
            $data = str_replace(array('(', ')'), '', $data);

            if (preg_match('/^\d{10}/', $data)) {
                array_push($numbers, $data);
            } else if (strlen($data) == 11) {
                $num = substr($data, 1);

                if (preg_match('/^\d{10}/', $num)) {
                    array_push($numbers, $num);
                }
            }
        }

        return $numbers;
    }

    /**
     * Extract all information
     * 
     * @return object Information object
     */
    public function extract()
    {
        $info = new \stdClass();

        $info->phoneNumbers = $this->checkPhoneNumbers();

        return $info;
    }

    /**
     * Return positions of all matches in array or return false.
     *
     * Setting $caseSensitive to false will search by just consonants, instead of both consonants and vowels.
     * $useVowels can be set to false to search vowels when $caseSensitive is true
     *
     * @example $search = $this->_search('query', 'complete string', false, false);
     *          OUTPUT: false
     *          
     * @param  string  $query         Query string
     * @param  string  $str           Search string
     * @param  boolean $caseSensitive Case Sensitive search
     * @param  boolean $useVowels     Enable vowel searching even when case sensitive
     * @return array                  Array of positions or false
 */
    private function _search($query, $str, $caseSensitive = true, $useVowels = true)
    {
        $matches = array();
        $numMatches = 0;
        $pos = 0;

        if (! $caseSensitive) {
            $str = strtolower($str);
            $query = strtolower($str);

            if ($useVowels) {
                $str = $this->_replace($str, 'aeiouyAEIOUY@wa@');
                $query = $this->_replace($query, 'aeiouyAEIOUY@wa@');
            }
        }

        while ($pos = strpos($str, $query, $pos)) {
            $matches[] = $pos;
            $pos = $pos + strlen($query);
            $numMatches++;
        }

        return (($numMatches === 0) ? false : $matches);
    }

    /**
     * Replace words in a string or array of strings with another word
     *
     * Using $replace - '@w@' is used as 'with', so 'apple@w@grape' means 'replace apple with grape'.
     * '@n@ is used as 'next.' It is used to seperate instructions, as follows, 'apple@w@grape@n@cherry@w@bannana'.
     * You can use '@wa@' instead of '@w@' to replace all instances of each character with another, like so, 'aeiouy@wa@#'
     * If the string were 'abcdefghijklmnopqrstuvwxyz', this would output '#bcd#fgh#jklmn#pqrst#vwx#z'.
     *
     * @example $this->_replace('this is a test', 'this@w@that@n@is@w@was'); 
     *          OUTPUT: 'that was a test'
     * 
     * @param  string|array $search  Search string (can be int)
     * @param  string       $replace Replace patterns
     * @return string|array          New string
     */
    private function _replace($search = '', $replace = '')
    {
        $isArray = $isInt = false;

        $strInfo = array();
        $strInfo['full'] = $replace;

        $strInfo['groups'] = explode('@n@', $strInfo['full']);

        if (is_array($search)) {
            $dataArray = array();
            $isArray = true;

            for ($i=0;$i<count($search);$i++) {
                $data = $search[$i];

                if (is_int($data)) {
                    $data = (string) $data;
                    $isInt = true;
                }

                for ($j=0;$j<count($strInfo['groups']);$j++) {
                    $group = $strInfo['groups'][$j];
                    $patternFound = $this->_search('@w@', $group, true);
                }

                if (! $patternFound) {
                    $groupInfo = explode('@w@', $group);
                    $pattern = $groupInfo[0];
                    $replacement = isset($groupInfo[1]) ? $groupInfo[1] : '';

                    $data = preg_replace($pattern, $replacement, $data);
                } else {
                    $groupInfo = explode('@wa@', $group);
                    $pattern = $groupInfo[0];
                    $replacement = isset($groupInfo[1]) ? $groupInfo[1] : '';

                    if ($this->_search('!NUM!', $pattern, true)) {
                        $pattern = '12334567890';
                    }

                    if ($this->_search('!VOWEL!', $pattern, true)) {
                        $pattern = 'aeiouAEIOU';
                    }

                    $pattern = str_split($pattern);

                    for ($k=0;$k<count($pattern);$k++) {
                        $data = str_replace($pattern[$k], $replacement, $data);
                    }
                }
            }

            if ($isInt) {
                $data = (int) $data;
            }

            array_push($dataArray, $data);
            $isInt = false;
        } else {
            if (is_int($search)) {
                $search = (string) $search;
                $isInt = true;
            }


            for ($j=0;$j<count($strInfo['groups']);$j++) {
                $group = $strInfo['groups'][$j];
                $patternFound = $this->_search('@w@', $group, true);

                if (! $patternFound) {
                    $groupInfo = explode('@w@', $group);
                    $pattern = $groupInfo[0];
                    $replacement = isset($groupInfo[1]) ? $groupInfo[1] : '';

                    $data = preg_replace($pattern, $replacement, $data);
                } else {
                    $groupInfo = explode('@wa@', $group);
                    $pattern = $groupInfo[0];
                    $replacement = isset($groupInfo[1]) ? $groupInfo[1] : '';

                    if ($this->_search('!NUM!', $pattern, true)) {
                        $pattern = '12334567890';
                    }

                    if ($this->_search('!VOWEL!', $pattern, true)) {
                        $pattern = 'aeiouAEIOU';
                    }

                    $pattern = str_split($pattern);

                    for ($k=0;$k<count($pattern);$k++) {
                        $search = str_replace($pattern[$k], $replacement, $search);
                    }
                }
            }

            if ($isInt) {
                $search = (int) $search;
            }
        }

        return (($isArray) ? $dataArray : $search);
    }

}
