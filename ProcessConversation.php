<?php

namespace ProcessConversation;

class ProcessConversation {

    /**
     * Raw data
     * @var string
     */
    private $data;

    /**
     * Lowercase Data
     * @var string
     */
    private $lcData;

    /**
     * Word Count
     * @var int
     */
    private $wordCount;

    /**
     * For link finding and third part of date
     * @var string
     */
    private $linkWords;

    /**
     * Constructor
     * @param string $data Data to process
     */
    public function __construct($data = '') {
        $lcData = strtolower($data);

        $this->data = $data;
        $this->wordCount = count(explode(' ', $lcData));
        $this->linkWords = preg_split('/[\s]+/', $lcData);
        $this->lcData = preg_split('/[\s]+/', $lcData);


        for ($i=0;$i<count($this->lcData);$i++) {
            $this->lcData[$i] = str_replace(array(' ', ',', '"'), '', $this->lcData[$i]);
            // $this->lcData[$i] = $this->_replace($this->lcData[$i], ' @w@@n@,@w@@n@"@w@');
            $this->lcData[$i] = str_replace('?', '', $this->lcData[$i]);
        }
    }

    /**
     * Return data word count
     * @return int Word Count
     */
    public function wordCount()
    {
        return (int) $this->wordCount;
    }

    /**
     * Estimated reading time in minutes
     * @return float Reading time (mins)
     */
    public function readingTime()
    {
        $readingSpeed = 0.312;

        return (($this->wordCount() * $readingSpeed ) / 60);
    }

    /**
     * Return array of all found phone numbers
     * @return array Array of phone numbers
     */
    public function checkPhoneNumbers()
    {
        $numbers = array();
        $pattern = '/(?>(\()?0\d(?(1)\))\s?+)?+\d\d\s?+(?>\d{3}\s?\d{3}|(?:\d\d\s??){3})/';

        foreach ($this->lcData as $data) {
            $data = $this->_replace($data, '-@w@');
            $data = str_replace(array('(', ')'), '', $data);

            if (preg_match($pattern, $data)) {
                array_push($numbers, $data);
            } else if (strlen($data) == 11) {
                $num = substr($data, 1);

                if (preg_match($pattern, $num)) {
                    array_push($numbers, $num);
                }
            }
        }

        return $numbers;
    }

    /**
     * Return array of all found email addresses
     * @return array Array of emails
     */
    public function checkEmails()
    {
        $emails = array();
        $pattern = '/^(?:[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+\.)*[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+@(?:(?:(?:[a-zA-Z0-9](?:[a-zA-Z0-9\-](?!\.)){0,61}[a-zA-Z0-9]?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9\-](?!$)){0,61}[a-zA-Z0-9]?)|(?:\[(?:(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\.){3}(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\]))$/';

        foreach ($this->lcData as $data) {
            $data = str_replace(array('(', ')', '!', ','), '', $data);

            if (preg_match($pattern, $data)) {
                array_push($emails, $data);
            }
        }
        
        return $emails;
    }

    /**
     * Return array of all found hyperlinks
     * @return array Array of links
     * 
     * @todo Return unique
     * @todo remove . from end of link if end of sentence
     */
    public function checkLinks()
    {
        $links = array();
        $pattern = '/^((http:\/\/www\.)|(www\.)|(http:\/\/))[a-zA-Z0-9._-]+\.[a-zA-Z.]{2,5}$/';

        foreach ($this->lcData as $data) {
            $data = str_replace(array('(', ')', '!', ','), '', $data);

            if (preg_match($pattern, $data)) {
                array_push($links, $data);
            }
        }
        
        return $links;
    }

    /**
     * Return array of all found times
     * @return array Array of times
     */
    public function checkTimes()
    {
        $times = array();

        foreach ($this->lcData as $key => $data) {
            $testTime = explode(':', $data);

            if (isset($testTime[0]) && isset($testTime[1])) {
                if ($testTime[0] > 0 && $testTime[0] < 13) {
                    if ($testTime[1] >= 0 && $testTime[1] < 61) {
                        if (isset($this->lcData[$key+1]) && strtolower($this->lcData[$key+1]) === 'pm') {
                            array_push($times, array('hours' => $testTime[0], 'mins' => $testTime[1], 'period' => "PM", 'full' => $testTime[0] . ':' . $testTime[1] . "pm"));
                        } else if (isset($this->lcData[$key+1]) && strtolower($this->lcData[$key+1]) === 'am') {
                            array_push($times, array('hours' => $testTime[0], 'mins' => $testTime[1], 'period' => "AM", 'full' => $testTime[0] . ':' . $testTime[1] . "am"));
                        }
                    }
                }
            }
        }

        return $times;
    }

    public function checkDates()
    {
        /*
        $dateVals['holidays'] = array(array('thanksgiving'), array('christmas'),
                                      array('new', 'years'), array('july', '4th'));
        $dateVals['holidaysDates'] = array(array(28, 11), array(25, 12), array(1, 1), array(4, 7));*/


    }

    public function getDay($word = '')
    {
        $days = array('1st', '2nd', '3rd', '4th', '5th', '6th', '7th',
                      '8th', '9th', '10th', '11th', '12th', '13th', '14th',
                      '15th', '16th', '17th', '18th', '19th', '20th',
                      '21st', '22nd', '23rd',  '24th', '25th', '26th', 
                      '27th', '28th', '29th', '30th', '31st');

        if (is_numeric($word)) {
            if ($word > 0 && $word < 32) {
                return (int) $word;
            }
        } else {
            for ($d=0;$d<count($days);$d++) {
                if ($days[$d] === $word) {   
                    return $d++;
                }
            }
        }
    }

    public function getMonth($word = '', $type = '')
    {
        $months = array('january', 'february', 'march', 'april', 'may', 
                        'june', 'july', 'august', 'september', 'october', 
                        'november', 'december');

        $monthAbbrevs = array('jan', 'feb', 'mar', 'apr', 'may', 'june', 
                             'july', 'aug', 'sept', 'oct', 'nov', 'dec');

        if (is_numeric($word) && $type === 'mdy') {
            return (int) $word;
        } else {
            for ($m=0;$m<count($months);$m++) {
                if ($months[$m] === $word) {   
                    return $m++;
                }
            }

            for ($m=0;$m<count($monthAbbrevs);$m++) {
                if ($monthAbbrevs[$m] === $word) {   
                    return $m++;
                }
            }
        }
    }

    /**
     * Count vowels in word
     * @param  string $word Word to check
     * @return int          Number of matches
     */
    public function vowelCount($word = '')
    {
        $search = preg_match_all('/[aeiou]/', $word, $matches);
        return ($matches ? count($matches) : 0);
    }

    /**
     * [consonantCount description]
     * @param  string $word Word to check
     * @return int          Number of matches
     */
    public function consonantCount($word = '')
    {
        $search = preg_match_all('/[bcdfghjklmnpqrstvwxyz]/', $word, $matches);
        return ($matches ? count($matches) : 0);
    }

    /**
     * [specialCharCount description]
     * @param  string $word Word to check
     * @return int          Number of matches
     */
    public function specialCharCount($word = '')
    {
        $search = preg_match_all('/[1234567890@#$%^&*();]/', $word, $matches);
        return ($matches ? count($matches) : 0);
    }

    /**
     * Check for spam
     * @return boolean Is text Spam
     */
    public function isSpam()
    {
        $isSpam = false;
        $details = '';

        // average word length
        $totalLength = 0;
        $averageLength = 0;

        // counts
        $vowelCount = 0;
        $consonantCount = 0;
        $specialCharCount = 0;

        // characters found
        $foundChars = array();
        $uniqueChars = count($foundChars);
        $useableChars = str_split('abcdefghijklmnopqrstuvwxyz');

        foreach ($this->lcData as $data) {
            // total num characters
            $totalLength += strlen($data);

            // character counts
            $vowelCount += $this->vowelCount($data);
            $consonantCount += $this->consonantCount($data);
            $specialCharCount += $this->specialCharCount($data);

            foreach (str_split($data) as $char) {
                $isThere = false;

                /*$c = preg_split('/[.?! ]+/', $char);
                $c = $c[0];

                $currentLoc = 0;

                while ($currentLoc < $this->wordCount() - 2) {
                    for ($i=$currentLoc+1;$i<$this->wordCount();$i++) {
                        $isUnuseable = true;

                        foreach ($useableChars as $useableChar) {
                            if ($c[$currentLoc] === $useableChar) {
                                $isUnuseable = false;
                            }
                        }

                        if (! $isUnuseable) {
                            if ($c[$i] === $c[$currentLoc]) {
                                if ($c[$i+1] === $c[$currentLoc+1]) {
                                    if ($c[$i+1] === $c[$currentLoc+1]) {
                                        $isSpam = true;
                                    }
                                }
                            }
                        } else {
                            break;
                        }

                        $currentLoc++;
                    }
                }*/

                // create array of found characters
                foreach ($foundChars as $foundChar) {
                    if ($char === $foundChar) {
                        $isThere = true;
                    }
                }

                if (! $isThere) {
                    array_push($foundChars, $char);
                }
            }
        }

        $averageLength = ($totalLength / $this->wordCount());

        // average word length check
        if ($averageLength + 15 >= 5.1 && $averageLength - 15 <= 5.1) {
            ;
        } else {
            $isSpam = true;
            $details .= 'Average word length inconsistency... ';
        }

        if ($vowelCount > $consonantCount || $specialCharCount > $vowelCount) {
            $isSpam = true;
            $details .= 'More vowels than consonants or too many special characters... ';
        }

        $roughCount = ($consonantCount / 7);
        if ($this->wordCount() > 3) {
            if ($vowelCount + $roughCount >= ($consonantCount / 1.9) && $vowelCount - $roughCount <= ($consonantCount / 1.9)) {
                ;
            } else {
                $isSpam = true;
                $details .= 'vowels + roughcount > 2x consonants... ';
            }
        } else if ($this->wordCount() > 2) {
            if ($vowelCount + $roughCount >= ($consonantCount / 1.4) && $vowelCount - $roughCount <= ($consontantCount / 1.4)) {
                ;
            } else {
                $isSpam = true;
                $details .= 'vowels + roughcount > 1.5x consonants... ';
            }
        } else {
            if ($vowelCount + $roughCount >= ($consonantCount) && $vowelCount - $roughCount <= ($consontantCount)) {
                ;
            } else {
                $isSpam = true;
                $details .= 'vowels + roughcount > consonants... ';
            }
        }

        if ($uniqueChars + ($this->wordCount() / 7) < $this->wordCount()) {
            $isSpam = true;
            $details .= 'Too many unique characters... ';
        }

        return array('spam' => $isSpam, 'details' => $details);
    }

    /**
     * Extract all information
     * @return object Information object
     */
    public function extract()
    {
        $info = new \stdClass();

        $info->wordCount = $this->wordCount();
        $info->readingTime = $this->readingTime();
        $info->phoneNumbers = $this->checkPhoneNumbers();
        $info->emails = $this->checkEmails();
        $info->links = $this->checkLinks();
        $info->times = $this->checkTimes();
        $info->isSpam = $this->isSpam();

        return $info;
    }

    /**
     * Return positions of all matches in array or return false.
     *
     * Setting $caseSensitive to false will search by just consonants, instead of both consonants and vowels.
     * $useVowels can be set to false to search vowels when $caseSensitive is true
     * @example $search = $this->_search('query', 'complete string', false, false);
     *          OUTPUT: false     
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
     * @example $this->_replace('this is a test', 'this@w@that@n@is@w@was'); 
     *          OUTPUT: 'that was a test'
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

                    $search = preg_replace($pattern, $replacement, $search);
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
