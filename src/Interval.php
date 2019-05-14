<?php
namespace Math;

use Math\Filter\FormatNumericString;

class Interval
{
    private $min = '';
    
    private $max = '';
    
    private $includeMin = false;
    
    private $includeMax = false;
    
    public function __construct($min, $max, $includeMin, $includeMax)
    {
        $min = strval($min);
        $max = strval($max);
        if ($min !== '' && !preg_match('/^\-?\d+(\.\d+)?$/', $min)) {
            throw new \InvalidArgumentException('Parameter min must be a numeric');
        }
        if ($max !== '' && !preg_match('/^\-?\d+(\.\d+)?$/', $max)) {
            throw new \InvalidArgumentException('Parameter max must be a numeric');
        }
        if (!is_bool($includeMin)) {
            throw new \InvalidArgumentException('Parameter includeMin must be a boolean');
        }
        if (!is_bool($includeMax)) {
            throw new \InvalidArgumentException('Parameter includeMax must be a boolean');
        }
        $formatNumericStringFliter = new FormatNumericString();
        $this->setMin($formatNumericStringFliter->filter($min));
        $this->setMax($formatNumericStringFliter->filter($max));
        $this->setIncludeMin($includeMin);
        $this->setIncludeMax($includeMax);
    }
    
    public function setMin($min)
    {
        $this->min = $min;
        return $this;
    }
    
    public function getMin()
    {
        return $this->min;
    }
    
    public function setMax($max)
    {
        $this->max = $max;
        return $this;
    }
    
    public function getMax()
    {
        return $this->max;
    }
    
    public function setIncludeMin($includeMin)
    {
        $this->includeMin = $includeMin;
        return $this;
    }
    
    public function getIncludeMin()
    {
        return $this->includeMin;
    }
    
    public function setIncludeMax($includeMax)
    {
        $this->includeMax = $includeMax;
        return $this;
    }
    
    public function getIncludeMax()
    {
        return $this->includeMax;
    }
    
    /**
     * Return true if this interval is an empty set
     * @return boolean
     */
    public function isEmptySet() {
        if ($this->getMin() !== '' && $this->getMax() !== '') {
            $comp = bccomp(
                $this->getMin(),
                $this->getMax(),
                max(
                    strlen(rtrim(substr(strstr($this->getMax(), "."), 1), '0')),
                    strlen(rtrim(substr(strstr($this->getMin(), "."), 1), '0'))
                )
            );
            return $comp === 1 || ($comp === 0 && (!$this->getIncludeMin() || !$this->getIncludeMax()));
        } else {
            return false;
        }
    }
    
    /**
     * Join with another interval
     * @param Interval $interval
     * @return \Math\Interval
     */
    public function joinWithAnotherInterval(Interval $interval)
    {
        return self::join(array($this, $interval));
    }
    
    
    /**
     * Join multiple intervals
     * @param array[\Math\Interval] $intervals 
     * @throws \InvalidArgumentException
     * @return \Math\Interval
     */
    public static function join(array $intervals)
    {
        if (count($intervals) >= 2) {
            return array_reduce(
                $intervals,
                function ($previousIntersection, $currentInterval) {
                    if ($currentInterval instanceof Interval) {
                        if ($previousIntersection->isEmptySet()) {
                            return $previousIntersection;
                        } elseif ($currentInterval->isEmptySet()) {
                            return $currentInterval;
                        } else {
                            if ($previousIntersection->getMin() === '') {
                                if ($currentInterval->getMin() === '') {
                                    $min = '';
                                    $includeMin = false;
                                } else {
                                    $min = $currentInterval->getMin();
                                    $includeMin = $currentInterval->getIncludeMin();
                                }
                            } else {
                                if ($currentInterval->getMin() === '') {
                                    $min = $previousIntersection->getMin();
                                    $includeMin = $previousIntersection->getIncludeMin();
                                } else {
                                    $minComparison = bccomp(
                                        $previousIntersection->getMin(),
                                        $currentInterval->getMin(),
                                        max(
                                            strlen(rtrim(substr(strstr($previousIntersection->getMin(), "."), 1), '0')),
                                            strlen(rtrim(substr(strstr($currentInterval->getMin(), "."), 1), '0'))
                                        )
                                    );
                                    switch ($minComparison) {
                                        case 1:
                                            $min = $previousIntersection->getMin();
                                            $includeMin = $previousIntersection->getIncludeMin();
                                            break;
                                        case -1:
                                            $min = $currentInterval->getMin();
                                            $includeMin = $currentInterval->getIncludeMin();
                                            break;
                                        case 0:
                                            $min = $previousIntersection->getMin();
                                            $includeMin = ($previousIntersection->getIncludeMin() && $currentInterval->getIncludeMin());
                                            break;
                                    }
                                }
                            }
                            // 获取交集的max和includeMax
                            if ($previousIntersection->getMax() === '') {
                                if ($currentInterval->getMax() === '') {
                                    $max = '';
                                    $includeMax = false;
                                } else {
                                    $max = $currentInterval->getMax();
                                    $includeMax = $currentInterval->getIncludeMax();
                                }
                            } else {
                                if ($currentInterval->getMax() === '') {
                                    $max = $previousIntersection->getMax();
                                    $includeMax = $previousIntersection->getIncludeMax();
                                } else {
                                    $maxComparison = bccomp(
                                        $previousIntersection->getMax(),
                                        $currentInterval->getMax(),
                                        max(
                                            strlen(rtrim(substr(strstr($previousIntersection->getMax(), "."), 1), '0')),
                                            strlen(rtrim(substr(strstr($currentInterval->getMax(), "."), 1), '0'))
                                        )
                                    );
                                    switch ($maxComparison) {
                                        case 1:
                                            $max = $currentInterval->getMax();
                                            $includeMax = $currentInterval->getIncludeMax();
                                            break;
                                        case -1:
                                            $max = $previousIntersection->getMax();
                                            $includeMax = $previousIntersection->getIncludeMax();
                                            break;
                                        case 0:
                                            $max = $previousIntersection->getMax();
                                            $includeMax = ($previousIntersection->getIncludeMax() && $currentInterval->getIncludeMax());
                                            break;
                                    }
                                }
                            }
                        }
                        return new Interval($min, $max, $includeMin, $includeMax);
                    } else {
                        throw new \InvalidArgumentException('The element in argument 1 must be an instance of interval');
                    }
                },
                new Interval('', '', false, false)
            );
        } else {
            throw new \InvalidArgumentException('The number of elements in argument 1 must be greater than 1');
        }
    }
    
    /**
     * Check whether a number is within the interval
     * @param $numeric
     * @throws \InvalidArgumentException
     * @return boolean
     */
    public function isContain($numeric)
    {
        if (preg_match('/^\-?\d+(\.\d+)?$/', $numeric)) {
            $numericDecimalPlaces = strlen(rtrim(substr(strstr($numeric, "."), 1), '0'));
            if ($this->getMin() !== '') {
                $compWithMin = bccomp($numeric, $this->getMin(), max(strlen(rtrim(substr(strstr($this->getMin(), "."), 1), '0')), $numericDecimalPlaces));
                if ($compWithMin === -1 || ($compWithMin === 0 && !$this->getIncludeMin())) {
                    return false;
                }
            }
            if ($this->getMax() !== '') {
                $compWithMax = bccomp($this->getMax(), $numeric, max(strlen(rtrim(substr(strstr($this->getMax(), "."), 1), '0')), $numericDecimalPlaces));
                if ($compWithMax === -1 || ($compWithMax === 0 && !$this->getIncludeMax())) {
                    return false;
                }
            }
            return true;
        } else {
             throw new \InvalidArgumentException('Argument must be a numeric');
        }
    }
    
    /**
     * get the decimal places interval for value of interval
     * @throws \RuntimeException
     * @return \Math\Interval
     */
    public function getDecimalPlacesIntervalForIntervalValue()
    {
        if (!$this->isEmptySet()) {
            if (
                $this->getMax() !== '' &&
                $this->getMin() !== '' &&
                !$this->isContain('0') &&
                !$this->isContain(bcadd($this->getMin(), 1))
            ) {
                $max = explode('.', $this->getMax());
                $min = explode('.', $this->getMin());
                $minDecimalPart = isset($min[1]) ? $min[1] : '';
                $minDecimalPlaces = strlen($minDecimalPart);
                $decimalPlacesDifference = strlen(isset($max[1]) ? $max[1] : '') - $minDecimalPlaces;
                $minDecimalPartValues = str_split($minDecimalPart . ($decimalPlacesDifference > 0 ? str_repeat('0', $decimalPlacesDifference) : ''));
                foreach ($minDecimalPartValues as $key => $value) {
                    $place = $key + 1;
                    if ($this->getIncludeMin() && $minDecimalPlaces <= $place) {
                        return new Interval($minDecimalPlaces, $this->getIncludeMax() && $this->getMax() === $this->getMin() ? $minDecimalPlaces : '', true, true);
                    } elseif ($this->isContain(bcadd($min[0] . '.' . implode('', array_slice($minDecimalPartValues, 0, $place)),'0.' . str_repeat('0', $key) . '1',$place))) {
                        return new Interval($place, '', true, false);
                    }
                }
                return new Interval($place + 1, '', true, false);
            } else {
                return new Interval('0', '', true, false);
            }
        } else {
            throw new \RuntimeException('Interval can not be an empty set');
        }
    }
    
    /**get the integer digits interval for value of interval
     * @throws \RuntimeException
     * @return \Math\Interval
     */
    public function getIntegerDigitsIntervalForIntervalValue()
    {
        if (!$this->isEmptySet()) {
            if ($this->getMax() !== '' && $this->getMin() !== '') {
                $maximumIntegerPart = explode('.', $this->getMax())[0];
                $minimumIntegerPart = explode('.', $this->getMin())[0];
                if ($this->isContain('0')) {
                    $intervalDigitsMinimum = 1;
                    $maximumIntegerLength = strlen($this->isContain($maximumIntegerPart) ? $maximumIntegerPart : bcsub($maximumIntegerPart, 1));
                    $minimumIntegerLength = strlen(ltrim($this->isContain($minimumIntegerPart) ? $minimumIntegerPart : bcadd($minimumIntegerPart, 1), '-'));
                    $intervalDigitsMaximum = $maximumIntegerLength > $minimumIntegerLength ? $maximumIntegerLength : $minimumIntegerLength;
                } else {
                    if (bccomp($maximumIntegerPart, 0) === 1) {
                        $intervalDigitsMinimum = strlen($this->isContain($minimumIntegerPart) ? $minimumIntegerPart : bcadd($minimumIntegerPart, 1));
                        $intervalDigitsMaximum = strlen($this->isContain($maximumIntegerPart) ? $maximumIntegerPart : bcsub($maximumIntegerPart, 1));
                    } else {
                        $intervalDigitsMinimum = strlen(ltrim($this->isContain($maximumIntegerPart) ? $maximumIntegerPart : bcsub($maximumIntegerPart, 1), '-'));
                        $intervalDigitsMaximum = strlen(ltrim($this->isContain($minimumIntegerPart) ? $minimumIntegerPart : bcadd($minimumIntegerPart, 1), '-'));
                    }
                }
                return new Interval($intervalDigitsMinimum, $intervalDigitsMaximum, true, true);
            } else {
                return new Interval(1, '', true, false);
            }
        } else {
            throw new \RuntimeException('Interval can not be an empty set');
        }
    }
}

