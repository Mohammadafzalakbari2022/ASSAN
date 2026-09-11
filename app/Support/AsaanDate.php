<?php

namespace App\Support;

use DateTimeInterface;

/**
 * ASAAN date formatting: Gregorian + Afghan Solar Hijri (Iranian algorithm, modified)
 *
 * Self-contained converter (no external dependencies) based on the well-known
 * 33-year cycle algorithm (jdf). Verified against reference dates.
 */
class AsaanDate
{
	/** Afghan month names (Dari) */
	public const MONTHS = [
		1 => 'Hamal', 2 => 'Saur', 3 => 'Jawza', 4 => 'Saratan', 5 => 'Asad', 6 => 'Sunbula',
		7 => 'Mizan', 8 => 'Aqrab', 9 => 'Qaws', 10 => 'Jadi', 11 => 'Dalv', 12 => 'Hoot',
	];

	public const MODE_AFGHAN = 'afghan';
	public const MODE_GREGORIAN = 'gregorian';
	public const MODE_DUAL = 'dual';


	/**
	 * Converts a Gregorian date to the (Afghan) Solar Hijri calendar.
	 *
	 * @param int $gy Gregorian year
	 * @param int $gm Gregorian month (1-12)
	 * @param int $gd Gregorian day (1-31)
	 * @return int[3] [Solar Hijri year, month, day]
	 */
	public static function gregorianToSolarHijri( int $gy, int $gm, int $gd ) : array
	{
		$cum = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];

		$gy2 = ( $gm > 2 ) ? $gy + 1 : $gy;
		$days = 355666 + ( 365 * $gy ) + intdiv( $gy2 + 3, 4 ) - intdiv( $gy2 + 99, 100 )
			+ intdiv( $gy2 + 399, 400 ) + $gd + $cum[$gm - 1];

		$jy = -1595 + 33 * intdiv( $days, 12053 );
		$days %= 12053;

		$jy += 4 * intdiv( $days, 1461 );
		$days %= 1461;

		if( $days > 365 )
		{
			$jy += intdiv( $days - 1, 365 );
			$days = ( $days - 1 ) % 365;
		}

		if( $days < 186 )
		{
			$jm = 1 + intdiv( $days, 31 );
			$jd = 1 + ( $days % 31 );
		}
		else
		{
			$jm = 7 + intdiv( $days - 186, 30 );
			$jd = 1 + ( ( $days - 186 ) % 30 );
		}

		return [(int) $jy, (int) $jm, (int) $jd];
	}


	/**
	 * Converts an (Afghan) Solar Hijri date back to the Gregorian calendar.
	 *
	 * @param int $jy Solar Hijri year
	 * @param int $jm Solar Hijri month (1-12)
	 * @param int $jd Solar Hijri day (1-30/31)
	 * @return int[3] [Gregorian year, month, day]
	 */
	public static function solarHijriToGregorian( int $jy, int $jm, int $jd ) : array
	{
		$jy += 1595;
		$days = -355668 + ( 365 * $jy ) + intdiv( $jy, 33 ) * 8 + intdiv( ( $jy % 33 ) + 3, 4 ) + $jd
			+ ( $jm < 7 ? ( $jm - 1 ) * 31 : ( $jm - 7 ) * 30 + 186 );

		$gy = 400 * intdiv( $days, 146097 );
		$days %= 146097;

		if( $days > 36524 )
		{
			$gy += 100 * intdiv( --$days, 36524 );
			$days %= 36524;
			if( $days >= 365 ) {
				$days++;
			}
		}

		$gy += 4 * intdiv( $days, 1461 );
		$days %= 1461;

		if( $days > 365 )
		{
			$gy += intdiv( $days - 1, 365 );
			$days = ( $days - 1 ) % 365;
		}

		$gd = $days + 1;
		$leap = ( $gy % 4 === 0 && $gy % 100 !== 0 ) || $gy % 400 === 0;
		$sal = [0, 31, $leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

		for( $gm = 0; $gm < 13 && $gd > $sal[$gm]; $gm++ ) {
			$gd -= $sal[$gm];
		}

		return [(int) $gy, (int) $gm, (int) $gd];
	}


	/**
	 * Formats a date according to the customer date mode.
	 *
	 * @param DateTimeInterface $date Date to format
	 * @param string $mode 'afghan', 'gregorian' or 'dual'
	 * @param bool $withTime Append "H:i" time
	 * @return string Formatted date string
	 */
	public static function format( DateTimeInterface $date, string $mode = self::MODE_AFGHAN, bool $withTime = false ) : string
	{
		$g = self::gregorianToSolarHijri( (int) $date->format( 'Y' ), (int) $date->format( 'n' ), (int) $date->format( 'j' ) );
		$time = $withTime ? ' ' . $date->format( 'H:i' ) : '';
		$afghan = sprintf( '%04d/%02d/%02d%s', $g[0], $g[1], $g[2], $time );
		$gregorian = $date->format( 'Y-m-d' ) . $time;

		switch( $mode )
		{
			case self::MODE_GREGORIAN:
				return $gregorian;
			case self::MODE_DUAL:
				return $gregorian . ' | ' . $afghan;
			case self::MODE_AFGHAN:
			default:
				return $afghan;
		}
	}


	/**
	 * Formats a date string (naive UTC, "Y-m-d H:i:s" or "Y-m-d") according to the mode.
	 *
	 * @param string $date Date string; empty string or null returns an empty string
	 * @param string $mode 'afghan', 'gregorian' or 'dual'
	 * @param bool $withTime Append "H:i" time if the source contains a time part
	 * @return string Formatted date string
	 */
	public static function formatString( ?string $date, string $mode = self::MODE_AFGHAN, bool $withTime = false ) : string
	{
		$date = (string) $date;

		if( $date === '' || $date === '0000-00-00 00:00:00' || $date === '0000-00-00' ) {
			return '';
		}

		$withTime = $withTime || ( strlen( $date ) > 10 );

		try {
			$dt = new \DateTimeImmutable( $date, new \DateTimeZone( date_default_timezone_get() ) );
		} catch( \Exception $e ) {
			return $date;
		}

		return self::format( $dt, $mode, $withTime );
	}
}