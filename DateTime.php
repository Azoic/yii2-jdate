<?php

namespace jDate;

use yii\base\InvalidParamException;

/**
 * Jalali date & time.
 * @author    Mohammad Mahdi Gholomian.
 * @copyright 2014 mm.gholamian@yahoo.com
 */
class DateTime extends \yii\base\Component
{
    public function date($dateFormat, $timeStamp = false)
    {
        if (!is_string($dateFormat)) {
            throw new InvalidParamException(
                "Date format is inavlid! " .
                "This must be a string."
            );
        }

        $dateTime = new \DateTime('@' . ($timeStamp ?: time()), new \DateTimeZone('Asia/Tehran'));
        $dateTime->setTimeZone(new \DateTimeZone('Asia/Tehran'));
        list($jalaliYear, $jalaliMonth, $jalaliDay) = $this->toJalaliDate(
            $year = $dateTime->format('Y'),
            $month = $dateTime->format('m'),
            $day = $dateTime->format('d'));

        $res          = '';
        $skipNextCahr = false;
        for ($i = 0; $i < strlen($dateFormat); $i++) {
            $char = $dateFormat[$i];
            if ($skipNextCahr) {
                $skipNextCahr = false;
                $res          .= $char;
                continue;
            }
            switch ($char) {
                case 'A' :
                    if ($dateTime->format('a') == 'pm') {
                        $res .= 'بعد از ظهر';
                    } else {
                        $res .= 'قبل از ظهر';
                    }
                    break;
                case 'a' :
                    if ($dateTime->format('a') == 'pm') {
                        $res .= 'ب.ظ';
                    } else {
                        $res .= 'ق.ظ';
                    }
                    break;
                case 'd':
                    if ($jalaliDay < 10) {
                        $res .= '0' . $jalaliDay;
                    } else {
                        $res .= $jalaliDay;
                    }
                    break;
                case 'D':
                    $res .= $this->getDayShortName($dateTime->format('D'));
                    break;
                case 'F':
                    $res .= $this->getMonthName($jalaliMonth);
                    break;
                case 'g':
                    $res .= $dateTime->format('g');
                    break;
                case 'G':
                    $res .= $dateTime->format('G');
                    break;
                case 'h':
                    $res .= $dateTime->format('h');
                    break;
                case 'H':
                    $res .= $dateTime->format('H');
                    break;
                case 'i':
                    $res .= $dateTime->format('i');
                    break;
                case 'j':
                    $res .= $jalaliDay;
                    break;
                case 'l':
                    $res .= $this->getDayName($dateTime->format('l'));
                    break;
                case 'm':
                    if ($jalaliMonth < 10) {
                        $res .= '0' . $jalaliMonth;
                    } else {
                        $res .= $jalaliMonth;
                    }
                    break;
                case 'M':
                    $res .= $this->getShortMonthName($jalaliMonth);
                    break;
                case 'n':
                    $res .= $jalaliMonth;
                    break;
                case 's':
                    $res .= $dateTime->format('s');
                    break;
                case 'S':
                    $res .= 'م';
                    break;
                case 't':
                    $res .= $this->daysInMonth($month, $day, $year);
                    break;
                case 'w':
                    $res .= $dateTime->format('w');
                    break;
                case "y":
                    $res .= substr($jalaliYear, 2);
                    break;
                case 'o':
                case "Y":
                    $res .= $jalaliYear;
                    break;
                case "\\" :
                    $skipNextCahr = true;
                    break;
                default:
                    $res .= $char;
                    break;
            }
        }

        return $res;
    }

    public function __get($var)
    {
        return $this->date($var);
    }

    public function toJalaliDate($g_y, $g_m, $g_d)
    {
        $g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];
        $gy              = $g_y - 1600;
        $gm              = $g_m - 1;
        $gd              = $g_d - 1;
        $g_day_no        = 365 * $gy + $this->div($gy + 3, 4) - $this->div($gy + 99, 100) + $this->div($gy + 399, 400);
        for ($i = 0; $i < $gm; ++$i)
            $g_day_no += $g_days_in_month[$i];

        if ($gm > 1 && (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0)))
            /* leap and after Feb */
            $g_day_no++;
        $g_day_no += $gd;
        $j_day_no = $g_day_no - 79;
        $j_np     = $this->div($j_day_no, 12053); /* 12053 = 365*33 + 32/4 */
        $j_day_no = $j_day_no % 12053;
        $jy       = 979 + 33 * $j_np + 4 * $this->div($j_day_no, 1461); /* 1461 = 365*4 + 4/4 */
        $j_day_no %= 1461;
        if ($j_day_no >= 366) {

            $jy += $this->div($j_day_no - 1, 365);

            $j_day_no = ($j_day_no - 1) % 365;

        }
        for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; ++$i)
            $j_day_no -= $j_days_in_month[$i];

        $jm = $i + 1;
        $jd = $j_day_no + 1;
        return [$jy, $jm, $jd];

    }

    public function toGregorianDate($Date, $mod = '/')
    {
        if($Date === null) return null;
        list($jy, $jm, $jd) = explode('/', $Date);
        if ($jy > 979) {
            $gy = 1600;
            $jy -= 979;
        } else {
            $gy = 621;
        }
        $days = (365 * $jy) + (((int)($jy / 33)) * 8) + ((int)((($jy % 33) + 3) / 4)) + 78 + $jd + (($jm < 7) ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);
        $gy   += 400 * ((int)($days / 146097));
        $days %= 146097;
        if ($days > 36524) {
            $gy   += 100 * ((int)(--$days / 36524));
            $days %= 36524;
            if ($days >= 365) $days++;
        }
        $gy   += 4 * ((int)($days / 1461));
        $days %= 1461;
        if ($days > 365) {
            $gy   += (int)(($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        $gd = $days + 1;
        foreach ([0, 31, (($gy % 4 == 0 and $gy % 100 != 0) or ($gy % 400 == 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31] as $gm => $v) {
            if ($gd <= $v) break;
            $gd -= $v;
        }
        $gm = $gm > 9 ?: '0'.$gm;
        return ($mod == '') ? [$gy, $gm, $gd] : $gy . $mod . $gm . $mod . $gd;
    }

    public function toTimestamp($date)
    {
        return strtotime($date);
    }

    public function div($a, $b)
    {
        return (int)($a / $b);
    }

    public function getDayNames()
    {
        return [
            'saturday'  => 'شنبه',
            'sunday'    => 'یکشنیه',
            'monday'    => 'دوشنبه',
            'tuesday'   => 'سه شنبه',
            'wednesday' => 'چهارشنبه',
            'thursday'  => 'پنجشنبه',
            'friday'    => 'جمعه',

        ];
    }

    public function getDayShortNames()
    {
        return [
            'sat' => 'ش',
            'sun' => 'ی',
            'mon' => 'د',
            'tue' => 'س',
            'wed' => 'چ',
            'thu' => 'پ',
            'fri' => 'ج',
        ];
    }

    public function getDayName($day)
    {
        $day = strtolower($day);
        return isset($this->getDayNames()[$day]) ? $this->getDayNames()[$day] : false;
    }

    public function getDayShortName($day)
    {
        $day = strtolower($day);
        return isset($this->getDayShortNames()[$day]) ? $this->getDayShortNames()[$day] : false;
    }

    public function getMonthNames()
    {
        return [
            1  => 'فروردین',
            2  => 'اردیبهشت',
            3  => 'خرداد',
            4  => 'تیر',
            5  => 'مرداد',
            6  => 'شهریور',
            7  => 'مهر',
            8  => 'آبان',
            9  => 'آذر',
            10 => 'دی',
            11 => 'بهمن',
            12 => 'اسفند',
        ];
    }

    public function getShortMonthNames()
    {
        return [
            1  => 'فرو',
            2  => 'ارد',
            3  => 'خرد',
            4  => 'تیر',
            5  => 'مرد',
            6  => 'شهر',
            7  => 'مهر',
            8  => 'آبا',
            9  => 'آذر',
            10 => 'دی',
            11 => 'بهم',
            12 => 'اسف',
        ];
    }

    public function getMonthName($month)
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidParamException(
                "Invalid month number. "
            );
        }

        $month = (int)$month;
        return $this->getMonthNames()[$month];
    }

    public function getShortMonthName($month)
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidParamException(
                "Invalid month number. "
            );
        }

        $month = (int)$month;
        return $this->getMonthNames()[$month];
    }

    /**
     * Find numbers in month.
     */
    public function daysInMonth($month, $day, $year)
    {
        $jday2     = "";
        $jdate2    = "";
        $lastdayen = date("d", mktime(0, 0, 0, $month + 1, 0, $year));
        list($jyear, $jmonth, $jday) = $this->toJalaliDate($year, $month, $day);
        $lastdatep = $jday;
        $jday      = $jday2;
        while ($jday2 != "1") {
            if ($day < $lastdayen) {
                $day++;
                list($jyear, $jmonth, $jday2) = $this->toJalaliDate($year, $month, $day);
                if ($jdate2 == "1") break;
                if ($jdate2 != "1") $lastdatep++;
            } else {
                $day = 0;
                $month++;
                if ($month == 13) {
                    $month = "1";
                    $year++;
                }
            }

        }
        return $lastdatep - 1;
    }
}
